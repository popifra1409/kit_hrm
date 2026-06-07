<?php

namespace App\Enums;

class EmployeeClassification
{
    /**
     * Nomenclature des catégories et échelons camerounais
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
     * Obtenir les options pour Select (catégorie)
     */
    public static function getCategoryOptions(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Obtenir les options pour Select (échelon)
     */
    public static function getEchelonOptions(): array
    {
        return self::ECHELONS;
    }
}
