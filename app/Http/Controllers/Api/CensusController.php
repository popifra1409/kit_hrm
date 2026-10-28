<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CensusCampaign;
use App\Models\CensusSubmission;
use App\Models\Dependent;
use App\Models\SalaryGrid;
use App\Support\CameroonCivilServiceGrid;
use App\Support\OrganizationalAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Recensement
 */
class CensusController extends Controller
{
    /**
     * Formats et taille d'une pièce jointe : la promesse faite à l'écran (« PDF, JPG ou
     * PNG — 4 Mo maximum ») est exactement la limite appliquée ici, sinon l'employé
     * croit sa pièce jointe et la soumission échoue sans explication reconnaissable.
     */
    private const DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    /** Une photo ne se livre pas en PDF. */
    private const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /** 4 Mio, comme la règle « max:4096 » des champs validés. */
    private const MAX_DOCUMENT_BYTES = 4 * 1024 * 1024;
    /**
     * État actuel du recensement pour l'employé connecté.
     */
    public function current(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $campaign = CensusCampaign::currentlyOpen();

        if (!$campaign) {
            $ended = CensusCampaign::recentlyEnded();

            if ($ended) {
                return response()->json([
                    'campaign' => null,
                    'message' => "La campagne « {$ended->name} » est terminée depuis le {$ended->ends_at->format('d/m/Y à H:i')}. Contactez les RH si vous n'avez pas pu la compléter à temps.",
                ]);
            }

            return response()->json(['campaign' => null]);
        }

        $submission = CensusSubmission::where('census_campaign_id', $campaign->id)
            ->where('employee_id', $employee->id)
            ->first();

        $employee->loadMissing(['dependents', 'diplomas', 'tradeBody', 'qualification', 'contractType']);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
            'submission_status' => $submission?->status,
            'stage_label' => $submission?->stage_label,
            'rejection_reason' => $submission?->rejection_reason_for_employee,
            'rejection_fields' => $submission?->rejection_fields_for_employee ?? [],
            'cancellation_reason' => $submission?->cancellation_reason,
            'current_data' => [
                'personal' => [
                    'photo_url' => $employee->photo ? Storage::url($employee->photo) : null,
                    'matricule_fonction_publique' => $employee->matricule_fonction_publique,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'gender' => $employee->gender,
                    'birth_date' => $employee->birth_date?->format('Y-m-d'),
                    'marital_status' => $employee->marital_status,
                    'children_under_6' => $employee->children_under_6,
                    'total_children' => $employee->total_children,
                    'id_card_number' => $employee->id_card_number,
                    'recruitment_date' => $employee->recruitment_date?->format('Y-m-d'),
                    'service_start_date' => $employee->service_start_date?->format('Y-m-d'),
                    'phone' => $employee->phone,
                    'email' => $employee->email,
                    'address' => $employee->address,
                    'city' => $employee->city,
                    'bank_name' => $employee->bank_name,
                    'bank_account_number' => $employee->bank_account_number,
                    'cnps_number' => $employee->cnps_number,
                ],

                // Contrat de travail et prise de service : le numéro est celui de la
                // fiche, les deux pièces sont jointes par l'agent et vérifiées par les RH.
                'contract' => [
                    'contract_number' => $employee->contract_number,
                    'contract_type' => $employee->contractType?->name,
                    'contract_document_url' => $employee->contract_document_path
                        ? Storage::url($employee->contract_document_path)
                        : null,
                    'service_start_document_url' => $employee->service_start_document_path
                        ? Storage::url($employee->service_start_document_path)
                        : null,
                ],

                // Classification salariale : le TYPE (cameroon/numeric) est fixé
                // administrativement et non modifiable ici. L'employé choisit sa
                // catégorie/échelon parmi les options valides pour ce type ; l'indice
                // est calculé automatiquement (jamais saisi directement).
                'salary_classification' => [
                    'classification_type' => $employee->classification_type,
                    'current_category_number' => $employee->category_number,
                    'current_echelon_number' => $employee->echelon_number,
                    'current_indice' => $employee->indice,
                    'category_options' => $employee->classification_type === 'cameroon'
                        ? CameroonCivilServiceGrid::categoryOptions()
                        : array_combine(range(1, 12), range(1, 12)),
                    // Pour 'cameroon', les échelons valides dépendent de la catégorie
                    // choisie (H.ECH n'existe que pour A2) — fourni par catégorie.
                    'echelon_options_by_category' => $employee->classification_type === 'cameroon'
                        ? collect(CameroonCivilServiceGrid::categoryOptions())
                        ->mapWithKeys(fn($label, $cat) => [$cat => CameroonCivilServiceGrid::echelonOptions($cat)])
                        : collect(range(1, 12))->mapWithKeys(fn($cat) => [(string) $cat => array_combine(range(1, 12), range(1, 12))]),
                    // Table complète (catégorie, échelon, indice) pour ce type de
                    // classification — permet à l'app mobile/web de calculer et afficher
                    // l'indice en direct dès que l'employé choisit catégorie + échelon,
                    // sans aller-retour serveur supplémentaire.
                    'grid_rows' => SalaryGrid::where('classification_type', $employee->classification_type)
                        ->where('is_active', true)
                        ->get(['category', 'echelon', 'indice'])
                        ->map(fn($row) => ['category' => $row->category, 'echelon' => $row->echelon, 'indice' => $row->indice]),
                ],

                // Affectation ACTUELLE de l'employé (pré-remplissage des menus en cascade) :
                // branche, direction, département OU sous-direction, service, secteur,
                // corps de métier, qualification, poste, type de personnel, statut.
                // Tout ceci est appliqué réellement à la validation finale ; seuls
                // département/service/secteur sont stockés sur l'employé, la direction
                // et la sous-direction se déduisent du service.
                'organizational' => OrganizationalAssignment::currentFor($employee),

                // Listes à plat avec l'identifiant du parent : le web et le mobile
                // filtrent localement la cascade, sans appel serveur à chaque choix.
                'organization_options' => OrganizationalAssignment::options(),

                'dependents' => $employee->dependents->map(fn($d) => [
                    'id' => $d->id,
                    'relationship' => $d->relationship,
                    'first_name' => $d->first_name,
                    'last_name' => $d->last_name,
                    'birth_date' => $d->birth_date?->format('Y-m-d'),
                    'birth_place' => $d->birth_place,
                    'gender' => $d->gender,
                    'phone' => $d->phone,
                    'email' => $d->email,
                    'address' => $d->address,
                    // Le web s'en sert pour ne pas redemander une pièce déjà au dossier :
                    // l'acte de naissance qui prouve la filiation sert aux deux parents.
                    'has_birth_certificate' => !empty($d->birth_certificate_path),
                ]),
                'diplomas' => $employee->diplomas->map(fn($d) => [
                    'id' => $d->id,
                    'type' => $d->type,
                    'title' => $d->title,
                    'institution' => $d->institution,
                    'year_obtained' => $d->year_obtained,
                ]),
            ],
        ]);
    }

    /**
     * Soumettre (ou re-soumettre) le recensement complet.
     */
    public function submit(Request $request, int $campaignId)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $campaign = CensusCampaign::find($campaignId);

        if (!$campaign || !$campaign->isCurrentlyAccessible()) {
            return response()->json(['message' => "Cette campagne de recensement n'est plus ouverte."], 404);
        }

        $existing = CensusSubmission::where('census_campaign_id', $campaign->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing && !$existing->canBeResubmitted()) {
            return response()->json(['message' => 'Ce recensement a déjà été validé et ne peut plus être modifié.'], 403);
        }

        // Quand le corps dépasse post_max_size, PHP jette TOUT : ni champs ni pièces.
        // Le formulaire passerait alors pour un recensement vide et écraserait la
        // soumission précédente. Un envoi portant une longueur mais sans aucun champ
        // ni aucune pièce ne peut venir que de là.
        if ($request->request->count() === 0
            && $request->files->count() === 0
            && (int) $request->headers->get('Content-Length', 0) > 0) {
            $this->logMissingUpload($request, 'corps du formulaire arrivé vide');

            return response()->json([
                'message' => "Le formulaire est arrivé vide : avec les pièces jointes, ton envoi dépasse la taille "
                    . "totale acceptée par le serveur ({$this->postLimit()}). Choisis des photos plus légères, "
                    . "une ligne à la fois si besoin.",
            ], 422);
        }

        // PHP ne prévient pas quand un envoi dépasse max_file_uploads : il supprime
        // les fichiers de la fin du corps, sans code d'erreur. L'ayant droit arrive
        // donc avec son nom mais sans son acte, et l'employé ne peut pas le deviner.
        $declaredFiles = (int) $request->input('files_sent', 0);
        $receivedFiles = $this->countFiles($request->files->all());

        if ($declaredFiles > $receivedFiles) {
            $this->logMissingUpload($request, "corps tronqué : {$receivedFiles} pièces reçues sur {$declaredFiles} envoyées");

            return response()->json([
                'message' => "Le serveur n'a reçu que {$receivedFiles} pièces sur les {$declaredFiles} que tu as "
                    . "jointes : il refuse silencieusement tout fichier au-delà de "
                    . ini_get('max_file_uploads') . " par envoi. Préviens le Service RH pour que la limite "
                    . "du serveur soit relevée, et garde ton brouillon : rien n'est perdu.",
            ], 422);
        }

        $rules = [
            'personal' => ['required', 'array'],
            'personal.matricule_fonction_publique' => ['nullable', 'string', 'max:255'],
            'personal.category_number' => ['nullable', 'string', 'max:255'],
            'personal.echelon_number' => ['nullable', 'string', 'max:255'],
            'personal.first_name' => ['nullable', 'string', 'max:255'],
            'personal.last_name' => ['nullable', 'string', 'max:255'],
            'personal.gender' => ['nullable', 'in:M,F'],
            'personal.birth_date' => ['nullable', 'date'],
            'personal.marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'personal.children_under_6' => ['nullable', 'integer', 'min:0'],
            'personal.total_children' => ['nullable', 'integer', 'min:0'],
            'personal.id_card_number' => ['nullable', 'string', 'max:255'],
            'personal.recruitment_date' => ['nullable', 'date'],
            'personal.service_start_date' => ['nullable', 'date'],
            'personal.phone' => ['nullable', 'string', 'max:255'],
            'personal.email' => ['nullable', 'email', 'max:255'],
            'personal.address' => ['nullable', 'string', 'max:255'],
            'personal.city' => ['nullable', 'string', 'max:255'],
            'personal.bank_name' => ['nullable', 'string', 'max:255'],
            'personal.bank_account_number' => ['nullable', 'string', 'max:255'],
            'personal.cnps_number' => ['nullable', 'string', 'max:255'],

            // Le compte bancaire n'est pas une saisie comme les autres : le virement du
            // salaire dépend du compte que les GRH détiennent. Le formulaire web affiche
            // un avertissement et une case de reconnaissance dès que l'un de ces trois
            // champs change ; le serveur l'exige aussi, le client ne faisant pas foi.
            'bank_change_acknowledged' => ['nullable', 'boolean'],

            'photo' => ['nullable', 'image', 'max:4096'],

            // Contrat de travail : numéro laissé libre (les RH le corrigent à la revue)
            // et pièce jointe. Le document de la prise de service accompagne la date
            // déjà déclarée dans « personal ».
            'contract' => ['nullable', 'array'],
            'contract.contract_number' => ['nullable', 'string', 'max:255'],
            'contract_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'service_start_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // Affectation organisationnelle : appliquée réellement à la validation finale
            // (voir CensusValidationService). Validée par identifiant/valeur fermée, puis
            // contrôlée dans sa cohérence d'ensemble plus bas.
            'organizational' => ['nullable', 'array'],
            'organizational.declared_branch_type' => ['nullable', 'in:medical,administrative'],
            'organizational.declared_direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'organizational.declared_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'organizational.declared_sub_direction_id' => ['nullable', 'integer', 'exists:sub_directions,id'],
            'organizational.declared_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'organizational.declared_sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'organizational.declared_job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'organizational.declared_trade_body_id' => ['nullable', 'integer', 'exists:trade_bodies,id'],
            'organizational.declared_qualification_id' => ['nullable', 'integer', 'exists:qualifications,id'],
            'organizational.declared_personnel_type' => ['nullable', 'in:soignant,non_soignant,paramedical,autres'],
            'organizational.declared_administrative_status' => ['nullable', 'in:fonctionnaire_affecte,fonctionnaire_detache,contractuel_structure'],

            'dependents' => ['array'],
            'dependents.*.existing_id' => ['nullable', 'integer'],
            'dependents.*.relationship' => ['required_with:dependents', 'in:spouse,child,father,mother'],
            'dependents.*.last_name' => ['required_with:dependents', 'string', 'max:255'],
            'dependents.*.first_name' => ['nullable', 'string', 'max:255'],
            'dependents.*.birth_date' => ['required_with:dependents', 'date'],
            'dependents.*.birth_place' => ['nullable', 'string', 'max:255'],
            'dependents.*.gender' => ['required_with:dependents', 'in:M,F'],
            'dependents.*.phone' => ['nullable', 'string', 'max:255'],
            'dependents.*.email' => ['nullable', 'email', 'max:255'],
            'dependents.*.address' => ['nullable', 'string'],

            'diplomas' => ['array'],
            'diplomas.*.existing_id' => ['nullable', 'integer'],
            'diplomas.*.type' => ['required_with:diplomas', 'in:recruitment_diploma,highest_diploma,training'],
            'diplomas.*.title' => ['required_with:diplomas', 'string', 'max:255'],
            'diplomas.*.institution' => ['required_with:diplomas', 'string', 'max:255'],
            'diplomas.*.year_obtained' => ['required_with:diplomas', 'integer', 'min:1950', 'max:' . now()->format('Y')],
        ];

        // Les messages reprennent ces noms : « le champ dependents.0.birth_date » ne se
        // retrouve pas sur le formulaire, « la date de naissance de l'ayant droit
        // « MARTIN A » (enfant) » oui.
        $validator = Validator::make($request->all(), $rules, [], $this->validationAttributes($request, $rules));

        if ($validator->fails()) {
            $raw = $validator->errors()->all();

            // La mention « … est obligatoire quand tes ayants droit est présent » ne veut
            // rien dire pour l'employé : la ligne est là, c'est bien son champ qui manque.
            $problems = array_map(
                static fn (string $p): string => (string) preg_replace('/ quand .+$/iu', '', $p),
                $raw
            );

            // Trace côté serveur : sans elle, un employé qui décrit « Données invalides »
            // ne peut pas être aiguillé, et le champ fautif peut avoir été effacé du
            // brouillon entre-temps.
            Log::warning('Recensement — soumission refusée pour données invalides', [
                'user_id' => $request->user()?->id,
                'champs' => $validator->errors()->keys(),
                'messages' => $raw,
            ]);

            return response()->json([
                'message' => count($problems) === 1
                    ? 'Un champ bloque encore l\'envoi : ' . $problems[0]
                    : count($problems) . ' champs bloquent l\'envoi : ' . implode(' · ', array_slice($problems, 0, 4))
                        . (count($problems) > 4 ? ' · …' : ''),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Le serveur ne se fie jamais aux listes déroulantes du client : la chaîne
        // choisie (service ∈ département/sous-direction, secteur ∈ service,
        // qualification ∈ corps de métier…) doit être cohérente.
        $chainError = OrganizationalAssignment::validate($data['organizational'] ?? []);

        if ($chainError) {
            return response()->json([
                'message' => $chainError,
                'errors' => ['organizational' => [$chainError]],
            ], 422);
        }
        unset($data['photo']); // traité séparément ci-dessous, pas stocké tel quel dans le payload

        // Une pièce refusée par PHP (taille, transport coupé) n'est ni un fichier
        // valide ni un champ présent : hasFile() la taisait et elle se perdait sans
        // que l'employé soit prévenu. Elle est maintenant nommée et expliquée.
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            if (! $photo->isValid()) {
                return $this->uploadRejectedResponse($photo, 'pour ta photo');
            }

            $data['photo_path'] = $photo->store('census/profile-photos', 'public');
        }

        // Contrat : numéro normalisé (les espaces autour d'une saisie ne sont pas une
        // donnée) + pièces stockées dans le payload. Elles ne gagnent la fiche employé
        // qu'à la validation finale, comme la photo et les actes.
        $contract = $data['contract'] ?? [];
        $contractNumber = trim((string) ($contract['contract_number'] ?? ''));
        $contract['contract_number'] = $contractNumber !== '' ? $contractNumber : null;

        $contractDocument = $request->file('contract_document');

        if ($contractDocument instanceof UploadedFile) {
            if (! $contractDocument->isValid()) {
                return $this->uploadRejectedResponse($contractDocument, 'pour ton contrat de travail');
            }

            $contract['document_path'] = $contractDocument->store('census/contracts', 'public');
        }

        $serviceStartDocument = $request->file('service_start_document');

        if ($serviceStartDocument instanceof UploadedFile) {
            if (! $serviceStartDocument->isValid()) {
                return $this->uploadRejectedResponse($serviceStartDocument, 'pour ton document de prise de service');
            }

            $contract['service_start_document_path'] = $serviceStartDocument->store('census/service-start', 'public');
        }

        $data['contract'] = $contract;

        // Comparaison avec la fiche en cours : une valeur laissée identique n'est pas
        // une modification, et une case vide ne doit pas être exigée pour rien.
        $bankChanges = [];

        foreach (['bank_name', 'bank_account_number', 'cnps_number'] as $bankField) {
            $submitted = trim((string) ($data['personal'][$bankField] ?? ''));
            $recorded = trim((string) ($employee->{$bankField} ?? ''));

            if ($submitted !== '' && $submitted !== $recorded) {
                $bankChanges[] = $bankField;
            }
        }

        unset($data['bank_change_acknowledged']);

        if ($bankChanges !== []) {
            if (!$request->boolean('bank_change_acknowledged')) {
                return response()->json([
                    'message' => "Vous avez modifié vos informations bancaires ou CNPS : confirmez avoir lu l'avertissement sur le virement du salaire avant de soumettre.",
                    'errors' => ['bank_change_acknowledged' => ["Cochez la confirmation pour soumettre."]],
                ], 422);
            }

            // Trace pour l'agent qui validera l'étape Solde : quoi a changé, et que
            // l'agent a bien été averti.
            $data['bank_change'] = [
                'fields' => $bankChanges,
                'acknowledged_at' => now()->toIso8601String(),
            ];
        }

        // Indice calculé côté serveur (jamais confié au client) à partir du type de
        // classification FIXE de l'employé + de la catégorie/échelon qu'il vient de
        // choisir. Stocké dans le payload pour affichage immédiat côté RH.
        $category = $data['personal']['category_number'] ?? null;
        $echelon = $data['personal']['echelon_number'] ?? null;

        if ($category && $echelon) {
            $data['personal']['computed_indice'] = SalaryGrid::lookupIndice(
                $employee->classification_type,
                $category,
                $echelon
            );
        }

        // Pièces des ayants droit. Un père ou une mère se justifie par l'acte de
        // naissance DE L'EMPLOYÉ : une seule pièce sert donc aux deux parents, et un
        // parent déjà au dossier en tient lieu pour l'autre. Les lignes déjà créées
        // gardent leur propre fichier, rien n'est réécrit.
        $parentRelations = ['father', 'mother'];
        $declaredActs = [];

        // Les lignes sont modifiées pendant le parcours (chemin des pièces stockées) :
        // itérer « $data['dependents'] ?? [] » porterait les écritures sur une copie
        // temporaire, le chemin serait jeté et la ligne suivante serait refusée alors
        // que son fichier est bien arrivé.
        $dependents = $data['dependents'] ?? [];
        $diplomas = $data['diplomas'] ?? [];

        foreach ($dependents as $index => &$dependent) {
            foreach (['photo', 'id_card', 'birth_certificate', 'marriage_certificate'] as $docKey) {
                $file = $request->file("dependents.{$index}.{$docKey}");

                if (! $file instanceof UploadedFile) {
                    // Une valeur texte à la place d'un fichier : la pièce a quitté le
                    // formulaire sans son contenu. Sans ce test, le serveur ne pourrait
                    // que réclamer « un acte de naissance » que l'employé croit avoir joint.
                    $sent = $request->input("dependents.{$index}.{$docKey}");

                    if (is_string($sent) && $sent !== '') {
                        return response()->json([
                            'message' => "La pièce « {$sent} » de {$this->dependentLabel($dependent)} n'est pas "
                                . "arrivée sous forme de fichier. Ouvre sa ligne et reprends le document.",
                        ], 422);
                    }

                    continue;
                }

                if (! $file->isValid()) {
                    return $this->uploadRejectedResponse($file, "pour {$this->dependentLabel($dependent)}");
                }

                $refusal = $this->documentRefusal(
                    $file,
                    "de {$this->dependentLabel($dependent)}",
                    $docKey === 'photo' ? self::PHOTO_EXTENSIONS : self::DOCUMENT_EXTENSIONS
                );

                if ($refusal !== null) {
                    return $refusal;
                }

                $dependent['documents'][$docKey] = $file->store('census/dependents', 'public');
            }

            if (in_array($dependent['relationship'] ?? '', $parentRelations, true)
                && !empty($dependent['documents']['birth_certificate'])) {
                $declaredActs[] = $dependent['documents']['birth_certificate'];
            }
        }
        unset($dependent);

        $needInheritedAct = collect($dependents)->contains(
            fn(array $d) => empty($d['existing_id'])
                && in_array($d['relationship'] ?? '', $parentRelations, true)
                && empty($d['documents']['birth_certificate'])
        );

        $inheritedAct = $needInheritedAct && !$declaredActs
            ? Dependent::where('employee_id', $employee->id)
                ->whereIn('relationship', $parentRelations)
                ->whereNotNull('birth_certificate_path')
                ->value('birth_certificate_path')
            : null;

        foreach ($dependents as $index => &$dependent) {
            $label = $this->dependentLabel($dependent);

            if (empty($dependent['existing_id'])
                && in_array($dependent['relationship'] ?? '', $parentRelations, true)
                && empty($dependent['documents']['birth_certificate'])) {
                $reused = $declaredActs[0] ?? $inheritedAct;

                if ($reused) {
                    $dependent['documents']['birth_certificate'] = $reused;
                }
            }

            if (empty($dependent['existing_id']) && empty($dependent['documents']['birth_certificate'] ?? null)) {
                $this->logMissingUpload($request, "acte de naissance de {$label} absent de la requête");

                return response()->json([
                    'message' => in_array($dependent['relationship'] ?? '', $parentRelations, true)
                        ? "L'acte de naissance de l'employé est requis pour justifier la filiation avec {$label}. "
                            . "Ajoute-le sur sa ligne ({$this->documentAdvice()})."
                        : "L'acte de naissance de {$label} n'est pas arrivé au serveur. Ouvre sa ligne et sélectionne "
                            . "à nouveau le fichier ({$this->documentAdvice()}).",
                ], 422);
            }
        }
        unset($dependent);

        foreach ($diplomas as $index => &$diploma) {
            $file = $request->file("diplomas.{$index}.document");
            $sent = $request->input("diplomas.{$index}.document");

            if (! $file instanceof UploadedFile) {
                if (is_string($sent) && $sent !== '') {
                    return response()->json([
                        'message' => "Le justificatif « {$sent} » " . $this->diplomaLabel($diploma) . " n'est pas "
                            . "arrivé sous forme de fichier. Sélectionne-le à nouveau.",
                    ], 422);
                }
            } elseif (! $file->isValid()) {
                return $this->uploadRejectedResponse($file, 'pour le diplôme ' . $this->diplomaLabel($diploma));
            } else {
                $refusal = $this->documentRefusal($file, "justificatif du diplôme {$this->diplomaLabel($diploma)}");

                if ($refusal !== null) {
                    return $refusal;
                }

                $diploma['document_path'] = $file->store('census/diplomas', 'public');
            }

            if (empty($diploma['existing_id']) && empty($diploma['document_path'] ?? null)) {
                $this->logMissingUpload($request, 'justificatif du diplôme ' . $this->diplomaLabel($diploma) . ' absent de la requête');

                return response()->json([
                    'message' => "Le justificatif du diplôme " . $this->diplomaLabel($diploma) . " n'est pas arrivé "
                        . "au serveur. Sélectionne-le à nouveau ({$this->documentAdvice()}).",
                ], 422);
            }
        }
        unset($diploma);

        $data['dependents'] = $dependents;
        $data['diplomas'] = $diplomas;

        $submission = CensusSubmission::updateOrCreate(
            ['census_campaign_id' => $campaign->id, 'employee_id' => $employee->id],
            [
                'status' => CensusSubmission::STATUS_SUBMITTED,
                'payload' => $data,
                'submitted_at' => now(),
                'career_validated_by' => null,
                'career_validated_at' => null,
                'career_rejection_reason' => null,
                'career_rejection_fields' => null,
                'solde_validated_by' => null,
                'solde_validated_at' => null,
                'solde_rejection_reason' => null,
                'solde_rejection_fields' => null,
                'validated_by' => null,
                'validated_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'rejection_fields' => null,
                'canceled_by' => null,
                'canceled_at' => null,
                'cancellation_reason' => null,
            ]
        );

        return response()->json([
            'message' => 'Recensement soumis, en attente de validation.',
            'status' => $submission->status,
        ], 201);
    }

    /**
     * Noms français des champs validés, donnés au validateur pour qu'il les reprenne
     * dans ses messages. Les libellés viennent du catalogue que les RH utilisent déjà
     * pour renvoyer une fiche (CensusSubmission::correctableFields()), pour que l'agent
     * et l'employé nomment la même ligne ; les champs propres aux lignes d'ayant droit
     * et de diplôme, absents de ce catalogue, sont nommés ici.
     *
     * @return array<string, string> clé de règle => nom
     */
    private function validationAttributes(Request $request, array $rules): array
    {
        // Noms courts, car ils seront suivis de « de l'ayant droit … » / « du diplôme … ».
        $rowFields = [
            'relationship' => 'lien de parenté',
            'birth_place' => 'lieu de naissance',
            'existing_id' => 'référence déjà au dossier',
            'type' => 'type',
            'title' => 'intitulé',
            'institution' => 'établissement',
            'year_obtained' => 'année d\'obtention',
        ];

        // Le catalogue ne couvre pas les groupes ni les pièces : nommés ici.
        $singles = [
            'personal' => 'tes informations personnelles',
            'contract' => 'ton contrat de travail',
            'organizational' => 'ton affectation',
            'dependents' => 'tes ayants droit',
            'diplomas' => 'tes diplômes',
            'bank_change_acknowledged' => 'ta reconnaissance de l\'avertissement bancaire',
            'photo' => 'ta photo',
            'contract_document' => 'ton contrat de travail en pièce jointe',
            'service_start_document' => 'ton document de prise de service',
        ];

        $dependents = $request->input('dependents');
        $diplomas = $request->input('diplomas');

        $attributes = [];

        foreach (array_keys($rules) as $key) {
            $segments = explode('.', $key);
            $leaf = end($segments);
            $row = (int) ($segments[1] ?? 0);

            $attributes[$key] = match (true) {
                isset($singles[$key]) => $singles[$key],
                str_starts_with($key, 'dependents.') => ($rowFields[$leaf] ?? $this->lowerFirst(CensusSubmission::fieldLabel($leaf)))
                    . ' de ' . $this->dependentLabel($dependents[$row] ?? []),
                str_starts_with($key, 'diplomas.') => ($rowFields[$leaf] ?? $this->lowerFirst(CensusSubmission::fieldLabel($leaf)))
                    . ' du diplôme ' . $this->diplomaLabel($diplomas[$row] ?? []),
                default => $this->lowerFirst(CensusSubmission::fieldLabel($leaf)),
            };
        }

        return $attributes;
    }

    /** Un libellé de catalogue en début de phrase : « Adresse email » -> « adresse email ». */
    private function lowerFirst(string $label): string
    {
        return mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }

    /**
     * L'ayant droit tel que l'employé le reconnaît : « l'enfant « MARTIN A » », jamais
     * « position 0 » — un numéro de ligne ne se retrouve pas sur le formulaire.
     */
    private function dependentLabel(array $dependent): string
    {
        $who = trim(($dependent['first_name'] ?? '') . ' ' . ($dependent['last_name'] ?? ''));

        $relation = match ($dependent['relationship'] ?? '') {
            'spouse' => 'conjoint(e)',
            'child' => 'enfant',
            'father' => 'père',
            'mother' => 'mère',
            default => 'ayant droit',
        };

        return $who !== '' ? "l'ayant droit « {$who} » ({$relation})" : "l'ayant droit ({$relation})";
    }

    private function diplomaLabel(array $diploma): string
    {
        $title = trim((string) ($diploma['title'] ?? ''));

        return $title !== '' ? "« {$title} »" : '« intitulé non renseigné »';
    }

    /** Taille qu'une pièce ne doit pas dépasser, telle que le serveur la accepte vraiment. */
    private function uploadLimit(): string
    {
        return $this->humanBytes(UploadedFile::getMaxFilesize());
    }

    /** Taille totale maximale d'un envoi, réglée par post_max_size. */
    private function postLimit(): string
    {
        $raw = trim((string) ini_get('post_max_size'));
        $unit = strtolower(substr($raw, -1));
        $factor = ['g' => 1073741824, 'm' => 1048576, 'k' => 1024][$unit] ?? 1;

        return $this->humanBytes(((float) $raw) * $factor);
    }

    private function humanBytes(int|float $bytes): string
    {
        $number = static fn (int|float $value) => rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');

        if ($bytes >= 1073741824) {
            return $number($bytes / 1073741824) . ' Go';
        }

        if ($bytes >= 1048576) {
            return $number($bytes / 1048576) . ' Mo';
        }

        if ($bytes >= 1024) {
            return $number($bytes / 1024) . ' Ko';
        }

        return round($bytes) . ' octets';
    }

    /**
     * Trace du corps réellement reçu quand une pièce est déclarée absente. Côté
     * employé la pièce est bien jointe et la limite du serveur est large : seule la
     * structure de la requête dit si la part a quitté le navigateur, si elle est
     * arrivée vide, ou si elle a été coupée en route.
     */
    private function logMissingUpload(Request $request, string $what): void
    {
        Log::warning("Recensement — {$what}", [
            'user_id' => $request->user()?->id,
            'content_length' => (int) $request->headers->get('Content-Length', 0),
            'champs_recus' => $request->request->count(),
            'limite_upload' => $this->uploadLimit(),
            'pieces_declarees' => $request->input('files_sent'),
            'lignes_ayants_droit' => $this->describeDependents($request),
            'pieces_recues' => $this->describeFiles($request->files->all()),
        ]);
    }

    /**
     * Chaque ligne d'ayant droit telle que le serveur la lit, avec le sort de son
     * acte. Le nombre de lignes textuelles et leur index sont la seule façon de
     * distinguer « cette part n'a jamais été envoyée » (le navigateur n'avait pas
     * le fichier) de « elle a été envoyée puis perdue » (le serveur l'a coupée).
     */
    private function describeDependents(Request $request): array
    {
        $rows = $request->input('dependents');

        if (! is_array($rows)) {
            return [];
        }

        $out = [];

        foreach ($rows as $index => $row) {
            $act = $request->file("dependents.{$index}.birth_certificate");
            $name = trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''));

            $out[] = $index . ' · ' . ($name !== '' ? $name : 'sans nom')
                . ' · ' . ($row['relationship'] ?? '?')
                . ' · ' . (empty($row['existing_id']) ? 'nouveau' : 'déjà connu ' . $row['existing_id'])
                . ' · acte : ' . ($act instanceof UploadedFile
                    ? $act->getClientOriginalName()
                    : ($act === null ? 'absent' : 'pas un fichier'));
        }

        return $out;
    }

    /** Combien de pièces le serveur a réellement reçues, nichées comprises. */
    private function countFiles(mixed $value): int
    {
        if ($value instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return 1;
        }

        if (is_array($value)) {
            return array_sum(array_map(fn ($item) => $this->countFiles($item), $value));
        }

        return 0;
    }

    /**
     * Nom et poids de chaque part envoyée. Les clés sont conservées : une clé
     * absente signifie que la part n'a jamais atteint le serveur, une clé là mais
     * vide signifie que le navigateur l'a envoyée sans contenu.
     */
    private function describeFiles(mixed $value): mixed
    {
        // Le sac à fichiers contient parfois l'objet de Symfony plutôt que celui de
        // Laravel, qui en hérite : tester le parent couvre les deux.
        if ($value instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $size = $value->getError() === UPLOAD_ERR_OK
                ? ' · ' . $this->humanBytes((int) $value->getSize())
                : '';

            return $value->getClientOriginalName() . $size . ' · erreur ' . $value->getError();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->describeFiles($item), $value);
        }

        return $value === null ? 'part vide' : gettype($value);
    }

    /**
     * Une pièce rejetée par PHP (taille, transport coupé) arrive ici sous forme de
     * fichier invalide : sans message explicite, l'employé la croit envoyée et relance
     * la soumission dans une boucle sans fin.
     */
    private function uploadRejectedResponse(UploadedFile $file, string $for)
    {
        $reason = match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "dépasse la taille maximale acceptée ({$this->uploadLimit()}). Envoie une photo plus petite ou un PDF allégé.",
            UPLOAD_ERR_PARTIAL => "n'est arrivé qu'en partie. Sélectionne-le de nouveau avant de renvoyer.",
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => "n'a pas pu être enregistré sur le serveur. Réessaie, et préviens les RH si cela persiste.",
            default => "est illisible. Sélectionne-le de nouveau.",
        };

        return response()->json([
            'message' => "Le fichier « {$file->getClientOriginalName()} » {$for} {$reason}",
        ], 422);
    }

    /**
     * Refus d'une pièce pourtant bien arrivée : format non annoncé ou taille annoncée
     * dépassée. Les pièces des ayants droit et des diplômes ne passent par aucune règle
     * de validation, c'est donc ici que la limite promise à l'écran est appliquée.
     * Renvoie la réponse 422 à servir, ou null quand la pièce passe.
     */
    private function documentRefusal(UploadedFile $file, string $for, array $extensions = self::DOCUMENT_EXTENSIONS)
    {
        $formats = $this->formatsLabel($extensions);

        if (! in_array(strtolower($file->getClientOriginalExtension()), $extensions, true)) {
            return response()->json([
                'message' => "Le fichier « {$file->getClientOriginalName()} » {$for} n'est pas un format accepté. "
                    . "Envoie-le au format {$formats}.",
            ], 422);
        }

        if ($file->getSize() > self::MAX_DOCUMENT_BYTES) {
            return response()->json([
                'message' => "Le fichier « {$file->getClientOriginalName()} » {$for} pèse "
                    . "{$this->humanBytes((int) $file->getSize())} : la limite est "
                    . "{$this->humanBytes(self::MAX_DOCUMENT_BYTES)}. Reprends le scan en qualité réduite, "
                    . 'en noir et blanc, ou envoie une page à la fois.',
            ], 422);
        }

        return null;
    }

    /** « pdf, jpg, jpeg, png » se lit « PDF, JPG ou PNG » : JPEG n'est pas un format à part. */
    private function formatsLabel(array $extensions): string
    {
        // Le doublon JPEG se retire après la conversion, sinon « JPG » apparaît deux fois.
        $named = array_values(array_unique(array_map(
            static fn (string $ext): string => strtoupper($ext === 'jpeg' ? 'jpg' : $ext),
            $extensions
        )));

        $last = array_pop($named);

        return $named === [] ? $last : implode(', ', $named) . ' ou ' . $last;
    }

    /** Le rappel de ce qui est accepté, tel qu'affiché sous chaque champ de pièce jointe. */
    private function documentAdvice(): string
    {
        return $this->formatsLabel(self::DOCUMENT_EXTENSIONS) . ', ' . $this->humanBytes(self::MAX_DOCUMENT_BYTES) . ' maximum';
    }
}
