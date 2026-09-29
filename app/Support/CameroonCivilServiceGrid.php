<?php

namespace App\Support;

/**
 * Grille indiciaire officielle des fonctionnaires camerounais
 * (Ministère de la Fonction Publique / Ministère des Finances),
 * en vigueur à compter du 02 février 2024 (revalorisation de 5 %).
 *
 * Chaque ligne : [indice, salaire indiciaire brut (1), complément forfaitaire (2), indemnité de logement (3)]
 * Le TOTAL officiel est la somme (1) + (2) + (3), recalculée par SalaryGrid::getTotalSalaryAttribute().
 *
 * ⚠️ Trois lignes du document source présentent des incohérences internes (total imprimé
 *    ≠ somme des colonnes). Les composantes sont reprises telles qu'imprimées ; à faire
 *    vérifier contre l'original signé puis corriger via l'interface si besoin :
 *      - C  / 2/5 : total imprimé 114 178 (la somme donne 144 178 — faute de frappe évidente)
 *      - B2 / 2/5 : total imprimé 227 950 (la somme donne 228 300 — complément imprimé 900, total cohérent avec 550)
 *      - A2 / 2/1 : total imprimé 223 339 (la somme donne 223 309)
 */
class CameroonCivilServiceGrid
{
    public const EFFECTIVE_DATE = '2024-02-02';

    public const CATEGORIES = [
        'D' => 'D - Agents d\'exécution',
        'C' => 'C - Agents de maîtrise',
        'B1' => 'B1 - Cadres moyens',
        'B2' => 'B2 - Cadres moyens',
        'A1' => 'A1 - Cadres supérieurs',
        'A2' => 'A2 - Cadres supérieurs',
    ];

    public const ECHELONS = [
        'ST' => 'ST - Stagiaire',
        '2/1' => '2/1 - 2e classe, échelon 1',
        '2/2' => '2/2 - 2e classe, échelon 2',
        '2/3' => '2/3 - 2e classe, échelon 3',
        '2/4' => '2/4 - 2e classe, échelon 4',
        '2/5' => '2/5 - 2e classe, échelon 5',
        '2/6' => '2/6 - 2e classe, échelon 6',
        '2/7' => '2/7 - 2e classe, échelon 7',
        '1/1' => '1/1 - 1re classe, échelon 1',
        '1/2' => '1/2 - 1re classe, échelon 2',
        '1/3' => '1/3 - 1re classe, échelon 3',
        'CL Exc' => 'CL Exc - Classe exceptionnelle',
    ];

    /** Échelon supplémentaire existant uniquement pour la classe A2. */
    public const HORS_ECHELLE = ['H.ECH' => 'H.ECH - Hors échelle'];

    private const DATA = [
        'D' => [
            'ST' => [100, 50418, 2000, 10084],
            '2/1' => [105, 52939, 2000, 10588],
            '2/2' => [110, 55460, 2000, 11092],
            '2/3' => [115, 57981, 2000, 11596],
            '2/4' => [120, 60501, 2000, 12100],
            '2/5' => [140, 70585, 2000, 14117],
            '2/6' => [150, 75626, 2000, 15125],
            '2/7' => [165, 83189, 2000, 16638],
            '1/1' => [185, 93273, 2000, 18655],
            '1/2' => [200, 100836, 2000, 20167],
            '1/3' => [205, 103357, 2000, 20671],
            'CL Exc' => [210, 105878, 2000, 21176],
        ],
        'C' => [
            'ST' => [180, 90753, 2000, 18151],
            '2/1' => [185, 93273, 2000, 18655],
            '2/2' => [200, 100836, 2000, 20167],
            '2/3' => [210, 105878, 2000, 21176],
            '2/4' => [225, 113440, 2000, 22688],
            '2/5' => [235, 118482, 2000, 23696],
            '2/6' => [250, 126044, 2000, 25209],
            '2/7' => [270, 136128, 2000, 27226],
            '1/1' => [280, 141169, 2000, 28234],
            '1/2' => [295, 148733, 2000, 29747],
            '1/3' => [310, 153321, 2000, 30664],
            'CL Exc' => [330, 157456, 2000, 31491],
        ],
        'B1' => [
            'ST' => [270, 136128, 2000, 27226],
            '2/1' => [300, 153316, 900, 30663],
            '2/2' => [335, 158489, 900, 31698],
            '2/3' => [370, 165726, 900, 33145],
            '2/4' => [405, 172961, 900, 34592],
            '2/5' => [445, 181231, 900, 36246],
            '2/6' => [480, 188467, 900, 37693],
            '2/7' => [495, 191567, 550, 38313],
            '1/1' => [505, 193635, 550, 38727],
            '1/2' => [530, 198804, 550, 39761],
            '1/3' => [560, 205006, 550, 41001],
            'CL Exc' => [575, 208107, 550, 41621],
        ],
        'B2' => [
            'ST' => [290, 146211, 900, 29242],
            '2/1' => [335, 158489, 900, 31698],
            '2/2' => [375, 166759, 900, 33352],
            '2/3' => [420, 176062, 900, 35212],
            '2/4' => [445, 181231, 900, 36246],
            '2/5' => [485, 189500, 900, 37900],
            '2/6' => [540, 200871, 550, 40174],
            '2/7' => [560, 205006, 550, 41001],
            '1/1' => [575, 208107, 550, 41621],
            '1/2' => [610, 215342, 550, 43068],
            '1/3' => [650, 223612, 550, 44722],
            'CL Exc' => [685, 230848, 550, 46170],
        ],
        'A1' => [
            'ST' => [375, 166759, 900, 33352],
            '2/1' => [430, 178129, 900, 35626],
            '2/2' => [480, 188467, 900, 37693],
            '2/3' => [530, 198804, 550, 39761],
            '2/4' => [580, 209140, 550, 41828],
            '2/5' => [630, 219477, 550, 43895],
            '2/6' => [680, 229815, 550, 45963],
            '2/7' => [740, 242219, 550, 48444],
            '1/1' => [785, 251522, 550, 50304],
            '1/2' => [835, 261860, 550, 52372],
            '1/3' => [900, 275297, 550, 55059],
            'CL Exc' => [945, 284600, 550, 56920],
        ],
        'A2' => [
            'ST' => [430, 178129, 900, 35626],
            '2/1' => [465, 185336, 900, 37073],
            '2/2' => [530, 198804, 550, 39761],
            '2/3' => [605, 214309, 550, 42862],
            '2/4' => [665, 226714, 550, 45343],
            '2/5' => [715, 237050, 550, 47410],
            '2/6' => [785, 251522, 550, 50304],
            '2/7' => [870, 269095, 550, 53819],
            '1/1' => [940, 283567, 550, 56713],
            '1/2' => [1005, 297005, 550, 59401],
            '1/3' => [1050, 306308, 550, 61262],
            'CL Exc' => [1115, 319746, 550, 63949],
            'H.ECH' => [1140, 324915, 550, 64983],
        ],
    ];

    public static function categoryOptions(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Échelons disponibles pour une classe donnée (H.ECH uniquement pour A2).
     */
    public static function echelonOptions(?string $category = null): array
    {
        $options = self::ECHELONS;

        if ($category === 'A2') {
            $options += self::HORS_ECHELLE;
        }

        return $options;
    }

    /**
     * Tous les échelons possibles, toutes classes confondues (pour les filtres).
     */
    public static function allEchelonOptions(): array
    {
        return self::ECHELONS + self::HORS_ECHELLE;
    }

    public static function find(?string $category, ?string $echelon): ?array
    {
        if (!$category || !$echelon || !isset(self::DATA[$category][$echelon])) {
            return null;
        }

        [$indice, $brut, $complement, $logement] = self::DATA[$category][$echelon];

        return [
            'category' => $category,
            'echelon' => $echelon,
            'indice' => $indice,
            'salaire_indiciaire_brut' => $brut,
            'complement_forfaitaire' => $complement,
            'indemnite_logement' => $logement,
            'total' => $brut + $complement + $logement,
        ];
    }

    /**
     * Liste à plat de toutes les lignes officielles (pour l'import en base).
     */
    /**
     * Retrouve, dans la grille officielle des fonctionnaires, l'indice dont le
     * salaire indiciaire brut (colonne 1) est le plus proche d'un salaire donné.
     * Utilisé pour attribuer un indice "approximatif" aux contractuels, à salaire
     * de base égal ou quasi égal. Le résultat est toujours un indice réellement
     * existant dans la grille (donc entier, multiple de 5).
     */
    public static function nearestIndiceForSalary(float $salary): int
    {
        $bestIndice = null;
        $bestDiff = null;

        foreach (self::DATA as $echelons) {
            foreach ($echelons as $row) {
                [$indice, $brut] = $row;
                $diff = abs($brut - $salary);

                if ($bestDiff === null || $diff < $bestDiff) {
                    $bestDiff = $diff;
                    $bestIndice = $indice;
                }
            }
        }

        return $bestIndice;
    }

    public static function rows(): array
    {
        $rows = [];

        foreach (self::DATA as $category => $echelons) {
            foreach (array_keys($echelons) as $echelon) {
                $rows[] = self::find($category, $echelon);
            }
        }

        return $rows;
    }
}
