<?php

namespace App\Support;

use App\Models\CensusSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ce qui manque à une fiche employé pour que son recensement soit complet.
 *
 * Le catalogue vient de CensusSubmission::correctableFields(), la même liste que
 * celle proposée à l'agent qui renvoie un dossier : « champ manquant » ici = champ
 * que l'employé aurait pu renseigner. La preuve de la saisie est cherchée sur la
 * fiche (table employees), pas dans un payload : après validation, la fiche porte
 * la valeur définitive et les fiches jamais recensées apparaissent telles quelles.
 *
 * Les expressions sont du SQL PostgreSQL : elles servent à la fois au filtre
 * (whereRaw) et aux colonnes calculées, pour que le compteur affiché et le tri
 * disent la même chose que le filtre.
 */
class CensusGap
{
    /**
     * Clé du catalogue => colonne `employees` qui la porte.
     * 'text' = vide si NULL ou chaîne blanche ; 'null' = vide si NULL seulement
     * (un 0 d'enfants à charge est une réponse, pas un blanc).
     */
    private const COLUMNS = [
        'matricule_fonction_publique' => ['matricule_fonction_publique', 'text'],
        'last_name' => ['last_name', 'text'],
        'first_name' => ['first_name', 'text'],
        'gender' => ['gender', 'text'],
        'birth_date' => ['birth_date', 'null'],
        'marital_status' => ['marital_status', 'text'],
        'id_card_number' => ['id_card_number', 'text'],
        'children_under_6' => ['children_under_6', 'null'],
        'total_children' => ['total_children', 'null'],
        'recruitment_date' => ['recruitment_date', 'null'],
        'service_start_date' => ['service_start_date', 'null'],
        'phone' => ['phone', 'text'],
        'email' => ['email', 'text'],
        'address' => ['address', 'text'],
        'city' => ['city', 'text'],
        'category_number' => ['category_number', 'text'],
        'echelon_number' => ['echelon_number', 'text'],
        'photo' => ['photo', 'text'],
        'contract_number' => ['contract_number', 'text'],
        'contract_document' => ['contract_document_path', 'text'],
        'service_start_document' => ['service_start_document_path', 'text'],
        'declared_service_id' => [['current_service_id', 'service_id'], 'null'],
        'declared_sector_id' => ['sector_id', 'null'],
        'declared_trade_body_id' => ['trade_body_id', 'null'],
        'declared_qualification_id' => ['qualification_id', 'null'],
        'declared_job_title_id' => ['job_title_id', 'null'],
        'declared_personnel_type' => ['personnel_type', 'text'],
        'declared_administrative_status' => ['administrative_status', 'text'],
        'bank_name' => ['bank_name', 'text'],
        'bank_account_number' => ['bank_account_number', 'text'],
        'cnps_number' => ['cnps_number', 'text'],
    ];

    /** Clés évaluées sur une table liée : la fiche doit porter au moins une ligne. */
    private const RELATIONS = [
        'dependents' => ['dependents', 'employee_id'],
        'diplomas' => ['employee_diplomas', 'employee_id'],
    ];

    /**
     * Clés que la fiche ne peut pas porter seules : la branche, la direction, le
     * département et la sous-direction se déduisent du service (voir
     * OrganizationalAssignment::currentFor). Les compter comme manquantes
     * double-compterait declared_service_id.
     */
    public const DEDUCED = [
        'declared_branch_type',
        'declared_direction_id',
        'declared_department_id',
        'declared_sub_direction_id',
    ];

    /** Toutes les clés contrôlées, dans l'ordre du catalogue RH. */
    public static function keys(): array
    {
        return array_values(array_diff(array_keys(CensusSubmission::correctableFields()), self::DEDUCED));
    }

    /** Alias de la colonne calculée pour une clé. */
    public static function alias(string $key): string
    {
        return 'gap_' . $key;
    }

    /** Expression booléenne « ce champ est manquant », en SQL PostgreSQL. */
    public static function missingSql(string $key): string
    {
        if (isset(self::COLUMNS[$key])) {
            [$column, $kind] = self::COLUMNS[$key];

            // Le service peut être porté par l'affectation actuelle ou par le service
            // d'origine : le champ n'est manquant que si les deux sont vides.
            if (is_array($column)) {
                $blanks = array_map(
                    fn(string $c) => $kind === 'text'
                        ? "(employees.{$c} is null or btrim(employees.{$c}::text) = '')"
                        : "employees.{$c} is null",
                    $column
                );

                return '(' . implode(' and ', $blanks) . ')';
            }

            return $kind === 'text'
                ? "(employees.{$column} is null or btrim(employees.{$column}::text) = '')"
                : "(employees.{$column} is null)";
        }

        if (isset(self::RELATIONS[$key])) {
            [$table, $foreignKey] = self::RELATIONS[$key];

            return "(select exists (select 1 from {$table} r"
                . " where r.{$foreignKey} = employees.id and r.deleted_at is null)) is false";
        }

        throw new \InvalidArgumentException("Champ de recensement inconnu : {$key}");
    }

    /**
     * Les colonnes à ajouter à la requête : un booléen par champ contrôlé et le
     * total, pour que la ligne, le badge et le tri viennent du même calcul.
     */
    public static function selection(): array
    {
        $select = [];

        foreach (self::keys() as $key) {
            $select[] = DB::raw(self::missingSql($key) . ' as ' . self::alias($key));
        }

        $total = implode(' + ', array_map(
            fn(string $key) => '(case when ' . self::missingSql($key) . ' then 1 else 0 end)',
            self::keys()
        ));

        $select[] = DB::raw('(' . $total . ') as missing_count');

        return $select;
    }

    /**
     * Filtre « champ manquant » : un champ précis, ou les dossiers complets.
     * 'aucun' renvoie les fiches sans le moindre blanc.
     */
    public static function applyFilter(Builder $query, ?string $value): void
    {
        if (in_array($value, ['none', null, ''], true)) {
            if ($value === 'none') {
                $query->whereRaw('(' . implode(' or ', array_map([static::class, 'missingSql'], self::keys())) . ') = false');
            }

            return;
        }

        $query->whereRaw(self::missingSql($value));
    }

    /** Options du filtre, libellés identiques à ceux vus par l'agent et par l'employé. */
    public static function filterOptions(): array
    {
        $options = ['none' => '✔ Aucun champ manquant'];

        foreach (self::keys() as $key) {
            $options[$key] = CensusSubmission::fieldLabel($key);
        }

        return $options;
    }

    /** Clés manquantes d'une ligne déjà enrichie par selection(). */
    public static function missingKeys(Model $record): array
    {
        $missing = [];

        foreach (self::keys() as $key) {
            if (filter_var($record->getAttribute(self::alias($key)), FILTER_VALIDATE_BOOLEAN)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    public static function missingCount(Model $record): int
    {
        return (int) ($record->getAttribute('missing_count') ?? count(self::missingKeys($record)));
    }
}
