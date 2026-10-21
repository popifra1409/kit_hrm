<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusSubmission extends Model
{
    protected $fillable = [
        'census_campaign_id',
        'employee_id',
        'status',
        'payload',
        'submitted_at',
        'career_validated_by',
        'career_validated_at',
        'career_rejection_reason',
        'career_rejection_fields',
        'solde_validated_by',
        'solde_validated_at',
        'solde_rejection_reason',
        'solde_rejection_fields',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'rejection_fields',
        'canceled_by',
        'canceled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'career_rejection_fields' => 'array',
        'solde_rejection_fields' => 'array',
        'rejection_fields' => 'array',
        'submitted_at' => 'datetime',
        'career_validated_at' => 'datetime',
        'solde_validated_at' => 'datetime',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Statuts possibles, dans l'ordre du circuit :
     *   submitted         → en attente de validation Carrière
     *   career_validated  → en attente de validation Solde
     *   career_rejected   → renvoyé à l'employé (étape Carrière)
     *   solde_validated   → en attente de validation finale (admin)
     *   solde_rejected    → renvoyé à l'employé (étape Solde)
     *   validated         → validé définitivement, données appliquées
     *   rejected          → rejeté définitivement par l'admin
     *   canceled          → annulé par les RH avant toute application : l'employé
     *                       reprend un recensement vierge de toute validation
     */
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CAREER_VALIDATED = 'career_validated';
    public const STATUS_CAREER_REJECTED = 'career_rejected';
    public const STATUS_SOLDE_VALIDATED = 'solde_validated';
    public const STATUS_SOLDE_REJECTED = 'solde_rejected';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELED = 'canceled';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function campaign()
    {
        return $this->belongsTo(CensusCampaign::class, 'census_campaign_id');
    }

    public function careerValidatedBy()
    {
        return $this->belongsTo(User::class, 'career_validated_by');
    }

    public function soldeValidatedBy()
    {
        return $this->belongsTo(User::class, 'solde_validated_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    // --- Lecture de l'étape courante ---

    public function isAwaitingCareer(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isAwaitingSolde(): bool
    {
        return $this->status === self::STATUS_CAREER_VALIDATED;
    }

    public function isAwaitingFinalAdmin(): bool
    {
        return $this->status === self::STATUS_SOLDE_VALIDATED;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isRejected(): bool
    {
        return in_array($this->status, [
            self::STATUS_CAREER_REJECTED,
            self::STATUS_SOLDE_REJECTED,
            self::STATUS_REJECTED,
        ], true);
    }

    /**
     * Les RH peuvent-ils encore annuler ce recensement ? Uniquement tant qu'il est
     * en attente d'une étape : une fois validé, les données sont appliquées à la
     * fiche employé et seul un nouveau recensement (campagne suivante) pourra les
     * corriger ; un rejet leave déjà la main à l'employé.
     */
    public function isCancelable(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_CAREER_VALIDATED,
            self::STATUS_SOLDE_VALIDATED,
        ], true);
    }

    /**
     * L'employé peut-il encore modifier et resoumettre (tant que ce n'est pas
     * validé définitivement) ?
     */
    public function canBeResubmitted(): bool
    {
        return $this->status !== self::STATUS_VALIDATED;
    }

    public function getRejectionReasonForEmployeeAttribute(): ?string
    {
        return match ($this->status) {
            self::STATUS_CAREER_REJECTED => $this->career_rejection_reason,
            self::STATUS_SOLDE_REJECTED => $this->solde_rejection_reason,
            self::STATUS_REJECTED => $this->rejection_reason,
            default => null,
        };
    }

    /**
     * Les champs que l'étape qui a rejeté a signalés, prêts à afficher à l'employé :
     * une clé technique ne lui dirait rien, c'est le libellé du formulaire qu'il faut.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function getRejectionFieldsForEmployeeAttribute(): array
    {
        $fields = match ($this->status) {
            self::STATUS_CAREER_REJECTED => $this->career_rejection_fields,
            self::STATUS_SOLDE_REJECTED => $this->solde_rejection_fields,
            self::STATUS_REJECTED => $this->rejection_fields,
            default => null,
        };

        return collect($fields ?? [])
            ->map(fn($key) => ['key' => $key, 'label' => self::fieldLabel($key)])
            ->values()
            ->all();
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'En attente de validation Carrière',
            self::STATUS_CAREER_VALIDATED => 'En attente de validation Solde',
            self::STATUS_CAREER_REJECTED => 'Rejeté — étape Carrière',
            self::STATUS_SOLDE_VALIDATED => 'En attente de validation finale (Administrateur)',
            self::STATUS_SOLDE_REJECTED => 'Rejeté — étape Solde',
            self::STATUS_VALIDATED => 'Validé définitivement',
            self::STATUS_REJECTED => 'Rejeté définitivement',
            self::STATUS_CANCELED => 'Annulé par les RH — recensement à reprendre',
            default => $this->status,
        };
    }

    // --- Champs du payload par bucket (pour les écrans de revue) ---
    //
    // Structure du payload (inchangée dans sa forme, enrichie en contenu) :
    //   payload['personal']      → champs ci-dessous marqués (P), dont bank/cnps = Solde
    //   payload['organizational']→ tous les champs "declared_*"  = Carrière (appliqués à la validation finale)
    //   payload['photo_path']    → photo de profil, appliquée à la validation finale
    //   payload['dependents'], payload['diplomas'] → toujours Carrière

    public const CAREER_FIELDS = [
        // Dans payload['personal'] — appliqués à la validation finale
        'matricule_fonction_publique',
        'category_number',
        'echelon_number',
        'computed_indice',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'marital_status',
        'children_under_6',
        'total_children',
        'id_card_number',
        'recruitment_date',
        'service_start_date',
        'phone',
        'email',
        'address',
        'city',
        // Dans payload['organizational'] — tous appliqués à la validation finale
        'declared_branch_type',
        'declared_direction_id',
        'declared_department_id',
        'declared_sub_direction_id',
        'declared_service_id',
        'declared_sector_id',
        'declared_trade_body_id',
        'declared_qualification_id',
        'declared_job_title_id',
        'declared_personnel_type',
        'declared_administrative_status',
    ];

    public const SOLDE_FIELDS = [
        // Dans payload['personal']
        'bank_name',
        'bank_account_number',
        'cnps_number',
    ];

    // ========================================
    // CHAMPS QU'UN AGENT PEUT RENVOYER À L'EMPLOYÉ
    // ========================================
    //
    // Clés = celles du payload (personal.*, organizational.declared_*) ou les blocs
    // du formulaire web (photo, contract_*, dependents, diplomas). Les libellés
    // reprennent mot à mot ceux vus par l'employé : un rejet doit se comprendre
    // sans connaître le métier. computed_indice est volontairement absent, il est
    // calculé à partir de la catégorie et de l'échelon.

    public const CORRECTABLE_CAREER_FIELDS = [
        'matricule_fonction_publique' => 'Matricule Fonction Publique',
        'last_name' => 'Nom',
        'first_name' => 'Prénom',
        'gender' => 'Sexe',
        'birth_date' => 'Date de naissance',
        'marital_status' => 'Statut marital',
        'id_card_number' => "N° Carte d'identité",
        'children_under_6' => 'Enfants de moins de 6 ans',
        'total_children' => 'Nombre total d\'enfants',
        'recruitment_date' => 'Date de recrutement',
        'service_start_date' => 'Date de prise de service',
        'phone' => 'Téléphone',
        'email' => 'Adresse email',
        'address' => 'Adresse',
        'city' => 'Ville',
        'category_number' => 'Catégorie',
        'echelon_number' => 'Échelon',
        'photo' => 'Photo de profil',
        'contract_number' => 'Numéro de contrat',
        'contract_document' => 'Contrat de travail (pièce jointe)',
        'service_start_document' => 'Document de prise de service (pièce jointe)',
        'declared_branch_type' => "Branche d'affectation",
        'declared_direction_id' => 'Direction',
        'declared_department_id' => 'Département',
        'declared_sub_direction_id' => 'Sous-direction',
        'declared_service_id' => 'Service',
        'declared_sector_id' => 'Unité / Secteur',
        'declared_trade_body_id' => 'Corps de métier',
        'declared_qualification_id' => 'Qualification',
        'declared_job_title_id' => 'Poste hiérarchique',
        'declared_personnel_type' => 'Type de personnel',
        'declared_administrative_status' => 'Statut administratif',
        'dependents' => 'Ayants droit',
        'diplomas' => 'Diplômes & formations',
    ];

    public const CORRECTABLE_SOLDE_FIELDS = [
        'bank_name' => 'Nom de la banque',
        'bank_account_number' => 'Numéro de compte',
        'cnps_number' => 'Numéro CNPS',
    ];

    /**
     * Catalogue proposé à l'agent selon l'étape : Carrière, Solde, ou la liste complète
     * pour le contrôle final — un agent ne peut renvoyer que ce qu'il contrôle.
     */
    public static function correctableFields(string $stage = 'all'): array
    {
        return match ($stage) {
            'career' => self::CORRECTABLE_CAREER_FIELDS,
            'solde' => self::CORRECTABLE_SOLDE_FIELDS,
            default => self::CORRECTABLE_CAREER_FIELDS + self::CORRECTABLE_SOLDE_FIELDS,
        };
    }

    public static function fieldLabel(string $key): string
    {
        return self::correctableFields()[$key] ?? $key;
    }
}
