<?php

namespace App\Enums;

use App\Support\CameroonCivilServiceGrid;

class EmployeeClassification
{
    /**
     * Nomenclature des catégories et échelons camerounais (ancienne version simplifiée).
     *
     * ⚠️ Pour la grille indiciaire OFFICIELLE des fonctionnaires (classes D, C, B1, B2, A1, A2 et
     * échelons ST, 2/1 … CL Exc), utiliser getOfficialCategoryOptions() / getOfficialEchelonOptions().
     */
    public const CATEGORIES = [
        'A' => 'Catégorie A - Cadres supérieurs',
        'B' => 'Catégorie B - Cadres moyens',
        'C' => 'Catégorie C - Agents de maîtrise',
        'D' => 'Catégorie D - Agents d\'exécution',
        'E' => 'Catégorie E - Manoeuvres',
    ];

    public const ECHELONS = [
        '1' => 'Échelon 1',
        '2' => 'Échelon 2',
        '3' => 'Échelon 3',
        '4' => 'Échelon 4',
        '5' => 'Échelon 5',
        '6' => 'Échelon 6',
        '7' => 'Échelon 7',
        '8' => 'Échelon 8',
    ];

    /**
     * Obtenir toutes les classifications possibles (A1, A2, B1, etc.)
     */
    public static function getAllClassifications(): array
    {
        $classifications = [];

        foreach (array_keys(self::CATEGORIES) as $category) {
            foreach (array_keys(self::ECHELONS) as $echelon) {
                $key = "{$category}{$echelon}";
                $label = "{$category}{$echelon} - {$category} / Éch. {$echelon}";
                $classifications[$key] = $label;
            }
        }

        return $classifications;
    }

    /**
     * Extraire la catégorie et l'échelon d'une classification (A1 → A, 1)
     */
    public static function parse(string $classification): ?array
    {
        if (preg_match('/^([A-E])([1-8])$/', $classification, $matches)) {
            return [
                'category' => $matches[1],
                'echelon' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * Formater une classification
     */
    public static function format(string $category, string $echelon): string
    {
        return "{$category}{$echelon}";
    }

    /**
     * Obtenir le label d'une classification
     */
    public static function getLabel(string $classification): string
    {
        $classifications = self::getAllClassifications();
        return $classifications[$classification] ?? $classification;
    }

    /**
     * Obtenir les options pour Select (catégorie) — ancienne nomenclature simplifiée
     */
    public static function getCategoryOptions(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Obtenir les options pour Select (échelon) — ancienne nomenclature simplifiée
     */
    public static function getEchelonOptions(): array
    {
        return self::ECHELONS;
    }

    /**
     * Classes de la grille indiciaire OFFICIELLE des fonctionnaires (D, C, B1, B2, A1, A2).
     */
    public static function getOfficialCategoryOptions(): array
    {
        return CameroonCivilServiceGrid::categoryOptions();
    }

    /**
     * Échelons de la grille OFFICIELLE (dépendent de la classe : H.ECH n'existe que pour A2).
     */
    public static function getOfficialEchelonOptions(?string $category = null): array
    {
        return CameroonCivilServiceGrid::echelonOptions($category);
    }
}
