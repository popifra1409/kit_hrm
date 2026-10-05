<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Direction;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Qualification;
use App\Models\Sector;
use App\Models\Service;
use App\Models\SubDirection;
use App\Models\TradeBody;

/**
 * Affectation organisationnelle d'un employé — source unique de vérité pour :
 *   - les listes de choix exposées à l'API (recensement web et mobile) ;
 *   - la valeur actuelle d'un employé (pré-remplissage des formulaires) ;
 *   - le contrôle de cohérence de la chaîne choisie ;
 *   - l'application finale sur la fiche employé.
 *
 * Chaînes hiérarchiques :
 *   branche médicale        : Direction → Département → Service → Secteur/Unité
 *   branche administrative  : Direction → Sous-direction → Service → Secteur/Unité
 * puis, pour les deux branches : Corps de métier → Qualification, et Poste hiérarchique.
 *
 * La direction n'est jamais jugée sur son "type" : ce sont les liens qui comptent
 * (le département appartient-il à cette direction, la sous-direction aussi).
 *
 * Seuls département, service et secteur sont stockés sur l'employé
 * (department_id, current_service_id, sector_id) : la direction et la
 * sous-direction se déduisent du service (voir Employee::getDirectionAttribute()).
 */
class OrganizationalAssignment
{
    public const BRANCH_MEDICAL = 'medical';
    public const BRANCH_ADMINISTRATIVE = 'administrative';

    /** Types de service rattachables à la branche administrative. */
    public const ADMINISTRATIVE_SERVICE_TYPES = ['administrative', 'support', 'technical'];

    public const PERSONNEL_TYPES = [
        'soignant' => 'Soignant',
        'non_soignant' => 'Non Soignant',
        'paramedical' => 'Paramédical',
        'autres' => 'Autres',
    ];

    public const ADMINISTRATIVE_STATUSES = [
        'fonctionnaire_affecte' => 'Fonctionnaire Affecté',
        'fonctionnaire_detache' => 'Fonctionnaire Détaché',
        'contractuel_structure' => 'Contractuel de la Structure',
    ];

    // ------------------------------------------------------------------
    // Listes de choix (API)
    // ------------------------------------------------------------------

    /**
     * Toutes les listes nécessaires aux menus en cascade. Les listes sont à plat,
     * avec l'identifiant du parent, pour que web et mobile filtrent localement
     * sans aller-retour serveur à chaque sélection.
     */
    public static function options(): array
    {
        return [
            // Une direction est proposée selon ses ENFANTS réels, pas selon sa colonne "type"
            // (souvent absente ou différente dans les données existantes) : en branche
            // médicale celles qui ont des départements, en branche administrative celles
            // qui ont des sous-directions. Une direction peut figurer dans les deux.
            'directions' => Direction::active()
                ->withCount([
                    'departments as active_departments_count' => fn($q) => $q->where('is_active', true),
                    'subDirections as active_sub_directions_count' => fn($q) => $q->where('is_active', true),
                ])
                ->orderBy('order')->orderBy('name')
                ->get()
                ->map(fn(Direction $direction) => [
                    'id' => $direction->id,
                    'name' => $direction->name,
                    'has_departments' => $direction->active_departments_count > 0,
                    'has_sub_directions' => $direction->active_sub_directions_count > 0,
                ])
                ->values(),
            'departments' => Department::active()->orderBy('order')->orderBy('name')->get(['id', 'name', 'direction_id']),
            'sub_directions' => SubDirection::active()->orderBy('order')->orderBy('name')->get(['id', 'name', 'direction_id']),
            'services' => Service::active()->orderBy('order')->orderBy('name')
                ->get(['id', 'name', 'type', 'department_id', 'sub_direction_id']),
            'sectors' => Sector::active()->orderBy('order')->orderBy('name')->get(['id', 'name', 'service_id']),
            'trade_bodies' => TradeBody::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'qualifications' => Qualification::where('is_active', true)->orderBy('level_rank')
                ->get(['id', 'name', 'trade_body_id']),
            'job_titles' => JobTitle::where('is_active', true)->orderBy('hierarchy_level')
                ->get(['id', 'name', 'hierarchy_level']),
            'personnel_types' => self::toOptionList(self::PERSONNEL_TYPES),
            'administrative_statuses' => self::toOptionList(self::ADMINISTRATIVE_STATUSES),
        ];
    }

    private static function toOptionList(array $map): array
    {
        $list = [];

        foreach ($map as $value => $label) {
            $list[] = ['value' => $value, 'label' => $label];
        }

        return $list;
    }

    // ------------------------------------------------------------------
    // Valeur actuelle d'un employé
    // ------------------------------------------------------------------

    /**
     * Affectation actuelle de l'employé, dans le même format que celle qu'il
     * soumet (sans le préfixe "declared_"), pour pré-remplir les menus.
     */
    public static function currentFor(Employee $employee): array
    {
        $employee->loadMissing(['currentService.subDirection', 'department']);

        $service = $employee->currentService;
        $branch = ($service && !$service->isMedical())
            ? self::BRANCH_ADMINISTRATIVE
            : self::BRANCH_MEDICAL;

        $departmentId = null;
        $subDirectionId = null;
        $directionId = null;

        if ($branch === self::BRANCH_MEDICAL) {
            $departmentId = $employee->department_id ?? $service?->department_id;
            $department = $employee->department ?? ($departmentId ? Department::find($departmentId) : null);
            $directionId = $department?->direction_id;
        } else {
            $subDirectionId = $service?->sub_direction_id;
            $directionId = $service?->subDirection?->direction_id;
        }

        return [
            'branch_type' => $branch,
            'direction_id' => $directionId,
            'department_id' => $departmentId,
            'sub_direction_id' => $subDirectionId,
            'service_id' => $service?->id,
            'sector_id' => $employee->sector_id,
            'trade_body_id' => $employee->trade_body_id,
            'qualification_id' => $employee->qualification_id,
            'job_title_id' => $employee->job_title_id,
            'personnel_type' => $employee->personnel_type,
            'administrative_status' => $employee->administrative_status,
        ];
    }

    // ------------------------------------------------------------------
    // Cohérence de la chaîne
    // ------------------------------------------------------------------

    /**
     * Vérifie que les éléments choisis forment une chaîne cohérente (le service
     * appartient bien au département choisi, le secteur au service, etc.).
     * Les listes déroulantes l'empêchent déjà côté écran, mais le serveur ne doit
     * jamais s'y fier : une requête forgée ou une liste périmée pourrait sinon
     * enregistrer une affectation incohérente.
     *
     * @param  array $org Clés "declared_*" du payload du recensement.
     * @return string|null Message d'erreur en français, ou null si tout est cohérent.
     */
    public static function validate(array $org): ?string
    {
        return self::evaluate($org, [
            'direction' => self::row(Direction::class, $org['declared_direction_id'] ?? null, []),
            'department' => self::row(Department::class, $org['declared_department_id'] ?? null, ['direction_id']),
            'sub_direction' => self::row(SubDirection::class, $org['declared_sub_direction_id'] ?? null, ['direction_id']),
            'service' => self::row(Service::class, $org['declared_service_id'] ?? null, ['type', 'department_id', 'sub_direction_id']),
            'sector' => self::row(Sector::class, $org['declared_sector_id'] ?? null, ['service_id']),
            'qualification' => self::row(Qualification::class, $org['declared_qualification_id'] ?? null, ['trade_body_id']),
        ]);
    }

    /**
     * Cœur du contrôle, sans accès base de données (testable seul).
     *
     * @param array $rows Pour chaque niveau choisi, la ligne en base (ou null si introuvable).
     */
    public static function evaluate(array $org, array $rows): ?string
    {
        $chosen = fn(string $key) => !empty($org[$key]);
        $same = fn($a, $b) => (int) $a === (int) $b;

        // Un élément choisi doit exister encore (non supprimé).
        $levels = [
            'direction' => 'declared_direction_id',
            'department' => 'declared_department_id',
            'sub_direction' => 'declared_sub_direction_id',
            'service' => 'declared_service_id',
            'sector' => 'declared_sector_id',
            'qualification' => 'declared_qualification_id',
        ];

        foreach ($levels as $level => $key) {
            if ($chosen($key) && empty($rows[$level])) {
                return "Un des éléments choisis n'existe plus. Veuillez actualiser la liste et recommencer.";
            }
        }

        $branch = $org['declared_branch_type'] ?? null;

        $chainTouched = $chosen('declared_direction_id') || $chosen('declared_department_id')
            || $chosen('declared_sub_direction_id') || $chosen('declared_service_id')
            || $chosen('declared_sector_id');

        if ($chainTouched && !in_array($branch, [self::BRANCH_MEDICAL, self::BRANCH_ADMINISTRATIVE], true)) {
            return "La branche d'affectation (médicale ou administrative) est requise.";
        }

        if ($branch === self::BRANCH_MEDICAL) {
            if ($chosen('declared_sub_direction_id')) {
                return "Une sous-direction ne peut pas être choisie en branche médicale.";
            }
            if (
                $chosen('declared_department_id') && $chosen('declared_direction_id')
                && !$same($rows['department']['direction_id'], $org['declared_direction_id'])
            ) {
                return "Le département choisi n'appartient pas à cette direction.";
            }
            if ($chosen('declared_service_id')) {
                if ($rows['service']['type'] !== 'medical') {
                    return "Le service choisi n'est pas un service médical.";
                }
                if (
                    $chosen('declared_department_id')
                    && !$same($rows['service']['department_id'], $org['declared_department_id'])
                ) {
                    return "Le service choisi n'appartient pas à ce département.";
                }
            }
        }

        if ($branch === self::BRANCH_ADMINISTRATIVE) {
            if ($chosen('declared_department_id')) {
                return "Un département ne peut pas être choisi en branche administrative.";
            }
            if (
                $chosen('declared_sub_direction_id') && $chosen('declared_direction_id')
                && !$same($rows['sub_direction']['direction_id'], $org['declared_direction_id'])
            ) {
                return "La sous-direction choisie n'appartient pas à cette direction.";
            }
            if ($chosen('declared_service_id')) {
                if (!in_array($rows['service']['type'], self::ADMINISTRATIVE_SERVICE_TYPES, true)) {
                    return "Le service choisi n'est pas un service administratif, support ou technique.";
                }
                if (
                    $chosen('declared_sub_direction_id')
                    && !$same($rows['service']['sub_direction_id'], $org['declared_sub_direction_id'])
                ) {
                    return "Le service choisi n'appartient pas à cette sous-direction.";
                }
            }
        }

        if ($chosen('declared_sector_id')) {
            if (!$chosen('declared_service_id')) {
                return "Choisissez d'abord un service avant de choisir un secteur ou une unité.";
            }
            if (!$same($rows['sector']['service_id'], $org['declared_service_id'])) {
                return "Le secteur ou l'unité choisi n'appartient pas à ce service.";
            }
        }

        if ($chosen('declared_qualification_id')) {
            if (!$chosen('declared_trade_body_id')) {
                return "Choisissez d'abord un corps de métier avant de choisir une qualification.";
            }
            if (!$same($rows['qualification']['trade_body_id'], $org['declared_trade_body_id'])) {
                return "La qualification choisie n'appartient pas à ce corps de métier.";
            }
        }

        return null;
    }

    private static function row(string $model, $id, array $columns): ?array
    {
        if (empty($id)) {
            return null;
        }

        $record = $model::query()->find($id, array_merge(['id'], $columns));

        return $record ? $record->only(array_merge(['id'], $columns)) : null;
    }

    // ------------------------------------------------------------------
    // Application sur la fiche employé
    // ------------------------------------------------------------------

    /**
     * Applique l'affectation validée à la fiche employé (sans l'enregistrer : c'est
     * l'appelant qui fait le save(), dans sa transaction).
     *
     * Règles :
     *  - une valeur vide ne remplace jamais une valeur existante ;
     *  - si le corps de métier change sans qualification, l'ancienne qualification
     *    (qui appartenait à l'ancien corps) est retirée plutôt que laissée incohérente ;
     *  - l'affectation (département/service/secteur) n'est modifiée que si un service
     *    est choisi ; département n'est renseigné qu'en branche médicale, comme le
     *    wizard de création d'employé.
     */
    public static function applyTo(Employee $employee, array $org): void
    {
        if (!empty($org['declared_trade_body_id'])) {
            $tradeBodyChanged = (int) $employee->trade_body_id !== (int) $org['declared_trade_body_id'];

            if ($tradeBodyChanged && empty($org['declared_qualification_id'])) {
                $employee->qualification_id = null;
            }

            $employee->trade_body_id = $org['declared_trade_body_id'];
        }

        if (!empty($org['declared_qualification_id'])) {
            $employee->qualification_id = $org['declared_qualification_id'];
        }

        if (!empty($org['declared_job_title_id'])) {
            $employee->job_title_id = $org['declared_job_title_id'];
        }

        if (!empty($org['declared_personnel_type'])) {
            $employee->personnel_type = $org['declared_personnel_type'];
        }

        if (!empty($org['declared_administrative_status'])) {
            $employee->administrative_status = $org['declared_administrative_status'];
        }

        if (!empty($org['declared_service_id'])) {
            $employee->current_service_id = $org['declared_service_id'];
            $employee->sector_id = $org['declared_sector_id'] ?? null;
            $employee->department_id = ($org['declared_branch_type'] ?? null) === self::BRANCH_MEDICAL
                ? ($org['declared_department_id'] ?? null)
                : null;
        }
    }
}
