<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalaryGrid;
use Carbon\Carbon;

class SalaryGridSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table d'abord
        SalaryGrid::truncate();

        // Date d'application
        $effectiveDate = Carbon::create(2025, 1, 1);

        // Grille salariale complète : Catégories 3 à 12, Échelons 1 à 12
        // Basé sur la fonction publique camerounaise

        $grids = [
            // CATÉGORIE 3
            ['category' => 3, 'echelon' => 1, 'base_salary' => 45000],
            ['category' => 3, 'echelon' => 2, 'base_salary' => 47000],
            ['category' => 3, 'echelon' => 3, 'base_salary' => 49000],
            ['category' => 3, 'echelon' => 4, 'base_salary' => 51000],
            ['category' => 3, 'echelon' => 5, 'base_salary' => 53000],
            ['category' => 3, 'echelon' => 6, 'base_salary' => 55000],
            ['category' => 3, 'echelon' => 7, 'base_salary' => 57000],
            ['category' => 3, 'echelon' => 8, 'base_salary' => 59500],
            ['category' => 3, 'echelon' => 9, 'base_salary' => 62000],
            ['category' => 3, 'echelon' => 10, 'base_salary' => 64500],
            ['category' => 3, 'echelon' => 11, 'base_salary' => 67000],
            ['category' => 3, 'echelon' => 12, 'base_salary' => 70000],

            // CATÉGORIE 4
            ['category' => 4, 'echelon' => 1, 'base_salary' => 55000],
            ['category' => 4, 'echelon' => 2, 'base_salary' => 57500],
            ['category' => 4, 'echelon' => 3, 'base_salary' => 60000],
            ['category' => 4, 'echelon' => 4, 'base_salary' => 62500],
            ['category' => 4, 'echelon' => 5, 'base_salary' => 65000],
            ['category' => 4, 'echelon' => 6, 'base_salary' => 68000],
            ['category' => 4, 'echelon' => 7, 'base_salary' => 71000],
            ['category' => 4, 'echelon' => 8, 'base_salary' => 74000],
            ['category' => 4, 'echelon' => 9, 'base_salary' => 77500],
            ['category' => 4, 'echelon' => 10, 'base_salary' => 81000],
            ['category' => 4, 'echelon' => 11, 'base_salary' => 84500],
            ['category' => 4, 'echelon' => 12, 'base_salary' => 88000],

            // CATÉGORIE 5
            ['category' => 5, 'echelon' => 1, 'base_salary' => 70000],
            ['category' => 5, 'echelon' => 2, 'base_salary' => 73000],
            ['category' => 5, 'echelon' => 3, 'base_salary' => 76000],
            ['category' => 5, 'echelon' => 4, 'base_salary' => 79500],
            ['category' => 5, 'echelon' => 5, 'base_salary' => 83000],
            ['category' => 5, 'echelon' => 6, 'base_salary' => 87000],
            ['category' => 5, 'echelon' => 7, 'base_salary' => 91000],
            ['category' => 5, 'echelon' => 8, 'base_salary' => 95000],
            ['category' => 5, 'echelon' => 9, 'base_salary' => 99500],
            ['category' => 5, 'echelon' => 10, 'base_salary' => 104000],
            ['category' => 5, 'echelon' => 11, 'base_salary' => 108500],
            ['category' => 5, 'echelon' => 12, 'base_salary' => 113000],

            // CATÉGORIE 6
            ['category' => 6, 'echelon' => 1, 'base_salary' => 85000],
            ['category' => 6, 'echelon' => 2, 'base_salary' => 89000],
            ['category' => 6, 'echelon' => 3, 'base_salary' => 93000],
            ['category' => 6, 'echelon' => 4, 'base_salary' => 97500],
            ['category' => 6, 'echelon' => 5, 'base_salary' => 102000],
            ['category' => 6, 'echelon' => 6, 'base_salary' => 107000],
            ['category' => 6, 'echelon' => 7, 'base_salary' => 112000],
            ['category' => 6, 'echelon' => 8, 'base_salary' => 117500],
            ['category' => 6, 'echelon' => 9, 'base_salary' => 123000],
            ['category' => 6, 'echelon' => 10, 'base_salary' => 129000],
            ['category' => 6, 'echelon' => 11, 'base_salary' => 135000],
            ['category' => 6, 'echelon' => 12, 'base_salary' => 141000],

            // CATÉGORIE 7
            ['category' => 7, 'echelon' => 1, 'base_salary' => 100000],
            ['category' => 7, 'echelon' => 2, 'base_salary' => 105000],
            ['category' => 7, 'echelon' => 3, 'base_salary' => 110000],
            ['category' => 7, 'echelon' => 4, 'base_salary' => 116000],
            ['category' => 7, 'echelon' => 5, 'base_salary' => 122000],
            ['category' => 7, 'echelon' => 6, 'base_salary' => 128000],
            ['category' => 7, 'echelon' => 7, 'base_salary' => 135000],
            ['category' => 7, 'echelon' => 8, 'base_salary' => 142000],
            ['category' => 7, 'echelon' => 9, 'base_salary' => 150000],
            ['category' => 7, 'echelon' => 10, 'base_salary' => 158000],
            ['category' => 7, 'echelon' => 11, 'base_salary' => 166000],
            ['category' => 7, 'echelon' => 12, 'base_salary' => 175000],

            // CATÉGORIE 8
            ['category' => 8, 'echelon' => 1, 'base_salary' => 120000],
            ['category' => 8, 'echelon' => 2, 'base_salary' => 127000],
            ['category' => 8, 'echelon' => 3, 'base_salary' => 134208], // Exemple du bulletin
            ['category' => 8, 'echelon' => 4, 'base_salary' => 142000],
            ['category' => 8, 'echelon' => 5, 'base_salary' => 150000],
            ['category' => 8, 'echelon' => 6, 'base_salary' => 158500],
            ['category' => 8, 'echelon' => 7, 'base_salary' => 167500],
            ['category' => 8, 'echelon' => 8, 'base_salary' => 177000],
            ['category' => 8, 'echelon' => 9, 'base_salary' => 187000],
            ['category' => 8, 'echelon' => 10, 'base_salary' => 197500],
            ['category' => 8, 'echelon' => 11, 'base_salary' => 208500],
            ['category' => 8, 'echelon' => 12, 'base_salary' => 220000],

            // CATÉGORIE 9
            ['category' => 9, 'echelon' => 1, 'base_salary' => 145000],
            ['category' => 9, 'echelon' => 2, 'base_salary' => 153500],
            ['category' => 9, 'echelon' => 3, 'base_salary' => 162500],
            ['category' => 9, 'echelon' => 4, 'base_salary' => 172000],
            ['category' => 9, 'echelon' => 5, 'base_salary' => 182000],
            ['category' => 9, 'echelon' => 6, 'base_salary' => 192500],
            ['category' => 9, 'echelon' => 7, 'base_salary' => 203500],
            ['category' => 9, 'echelon' => 8, 'base_salary' => 215000],
            ['category' => 9, 'echelon' => 9, 'base_salary' => 227500],
            ['category' => 9, 'echelon' => 10, 'base_salary' => 240500],
            ['category' => 9, 'echelon' => 11, 'base_salary' => 254000],
            ['category' => 9, 'echelon' => 12, 'base_salary' => 268500],

            // CATÉGORIE 10
            ['category' => 10, 'echelon' => 1, 'base_salary' => 175000],
            ['category' => 10, 'echelon' => 2, 'base_salary' => 185500],
            ['category' => 10, 'echelon' => 3, 'base_salary' => 196500],
            ['category' => 10, 'echelon' => 4, 'base_salary' => 208000],
            ['category' => 10, 'echelon' => 5, 'base_salary' => 220500],
            ['category' => 10, 'echelon' => 6, 'base_salary' => 233500],
            ['category' => 10, 'echelon' => 7, 'base_salary' => 247000],
            ['category' => 10, 'echelon' => 8, 'base_salary' => 261500],
            ['category' => 10, 'echelon' => 9, 'base_salary' => 277000],
            ['category' => 10, 'echelon' => 10, 'base_salary' => 293000],
            ['category' => 10, 'echelon' => 11, 'base_salary' => 310000],
            ['category' => 10, 'echelon' => 12, 'base_salary' => 328000],

            // CATÉGORIE 11
            ['category' => 11, 'echelon' => 1, 'base_salary' => 210000],
            ['category' => 11, 'echelon' => 2, 'base_salary' => 223000],
            ['category' => 11, 'echelon' => 3, 'base_salary' => 236500],
            ['category' => 11, 'echelon' => 4, 'base_salary' => 251000],
            ['category' => 11, 'echelon' => 5, 'base_salary' => 266000],
            ['category' => 11, 'echelon' => 6, 'base_salary' => 282000],
            ['category' => 11, 'echelon' => 7, 'base_salary' => 298500],
            ['category' => 11, 'echelon' => 8, 'base_salary' => 316000],
            ['category' => 11, 'echelon' => 9, 'base_salary' => 334500],
            ['category' => 11, 'echelon' => 10, 'base_salary' => 354000],
            ['category' => 11, 'echelon' => 11, 'base_salary' => 375000],
            ['category' => 11, 'echelon' => 12, 'base_salary' => 397000],

            // CATÉGORIE 12
            ['category' => 12, 'echelon' => 1, 'base_salary' => 250000],
            ['category' => 12, 'echelon' => 2, 'base_salary' => 265500],
            ['category' => 12, 'echelon' => 3, 'base_salary' => 281500],
            ['category' => 12, 'echelon' => 4, 'base_salary' => 298500],
            ['category' => 12, 'echelon' => 5, 'base_salary' => 316500],
            ['category' => 12, 'echelon' => 6, 'base_salary' => 335500],
            ['category' => 12, 'echelon' => 7, 'base_salary' => 355500],
            ['category' => 12, 'echelon' => 8, 'base_salary' => 376500],
            ['category' => 12, 'echelon' => 9, 'base_salary' => 399000],
            ['category' => 12, 'echelon' => 10, 'base_salary' => 423000],
            ['category' => 12, 'echelon' => 11, 'base_salary' => 448500],
            ['category' => 12, 'echelon' => 12, 'base_salary' => 475500],
        ];

        foreach ($grids as $grid) {
            SalaryGrid::create([
                'category' => $grid['category'],
                'echelon' => $grid['echelon'],
                'base_salary' => $grid['base_salary'],
                'effective_date' => $effectiveDate,
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Grille salariale créée : Catégories 3-12, Échelons 1-12 (' . count($grids) . ' entrées)');
    }
}
