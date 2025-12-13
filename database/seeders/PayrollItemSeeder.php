<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayrollItem;

class PayrollItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // SALAIRE DE BASE
            [
                'name' => 'Salaire de Base',
                'code' => 'SALBASE',
                'type' => 'gain',
                'category' => 'base',
                'is_taxable' => true,
                'is_subject_to_cnps' => true,
                'calculation_method' => 'fixed',
                'display_order' => 1,
            ],

            // PRIMES IMPOSABLES
            [
                'name' => 'Prime d\'Ancienneté',
                'code' => 'PRIMEANC',
                'type' => 'gain',
                'category' => 'prime',
                'is_taxable' => true,
                'is_subject_to_cnps' => true,
                'calculation_method' => 'percentage',
                'percentage' => 20.0, // 20% du salaire de base
                'display_order' => 2,
                'description' => 'Calculée automatiquement : 20% du salaire de base',
            ],
            [
                'name' => 'Prime de Rendement',
                'code' => 'PRIMEREN',
                'type' => 'gain',
                'category' => 'prime',
                'is_taxable' => true,
                'is_subject_to_cnps' => true,
                'calculation_method' => 'fixed',
                'display_order' => 3,
            ],
            [
                'name' => 'Prime de Santé Publique',
                'code' => 'PRIMESANTE',
                'type' => 'gain',
                'category' => 'prime',
                'is_taxable' => true,
                'is_subject_to_cnps' => true,
                'calculation_method' => 'fixed',
                'fixed_amount' => 10000,
                'display_order' => 4,
            ],
            [
                'name' => 'Prime de Transport',
                'code' => 'PRIMETRANS',
                'type' => 'gain',
                'category' => 'prime',
                'is_taxable' => true,
                'is_subject_to_cnps' => true,
                'calculation_method' => 'fixed',
                'fixed_amount' => 20000,
                'display_order' => 5,
            ],

            // INDEMNITÉS NON IMPOSABLES
            [
                'name' => 'Indemnité de Logement',
                'code' => 'INDLOG',
                'type' => 'gain',
                'category' => 'indemnity',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'percentage',
                'percentage' => 20.0, // 20% du salaire de base
                'display_order' => 6,
            ],
            [
                'name' => 'Prime de Salissure',
                'code' => 'PRIMESAL',
                'type' => 'gain',
                'category' => 'indemnity',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'fixed',
                'display_order' => 7,
            ],
            [
                'name' => 'Prime d\'Écran',
                'code' => 'PRIMEECR',
                'type' => 'gain',
                'category' => 'indemnity',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'fixed',
                'display_order' => 8,
            ],

            // RETENUES SOCIALES
            [
                'name' => 'Contribution Pension Vieillesse',
                'code' => 'PENSION',
                'type' => 'deduction',
                'category' => 'social',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'percentage',
                'percentage' => 4.2, // 4.2% du salaire cotisable
                'display_order' => 20,
                'description' => 'Part employé CNPS - 4.2% du salaire cotisable',
            ],

            // IMPÔTS
            [
                'name' => 'IRPP',
                'code' => 'IRPP',
                'type' => 'deduction',
                'category' => 'tax',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'formula',
                'display_order' => 21,
                'description' => 'Calculé selon le barème progressif camerounais',
            ],
            [
                'name' => 'CAC (Centimes Additionnels Communaux)',
                'code' => 'CAC',
                'type' => 'deduction',
                'category' => 'tax',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'percentage',
                'percentage' => 10.0, // 10% de l'IRPP
                'display_order' => 22,
                'description' => '10% de l\'IRPP',
            ],
            [
                'name' => 'Taxe de Développement Local',
                'code' => 'TDL',
                'type' => 'deduction',
                'category' => 'tax',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'fixed',
                'fixed_amount' => 1000,
                'display_order' => 23,
            ],
            [
                'name' => 'Redevance Audiovisuelle',
                'code' => 'REDAUD',
                'type' => 'deduction',
                'category' => 'tax',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'fixed',
                'fixed_amount' => 1950,
                'display_order' => 24,
            ],

            // AUTRES RETENUES
            [
                'name' => 'Crédit Foncier',
                'code' => 'CREDFON',
                'type' => 'deduction',
                'category' => 'other_deduction',
                'is_taxable' => false,
                'is_subject_to_cnps' => false,
                'calculation_method' => 'percentage',
                'percentage' => 1.0, // 1% du salaire imposable
                'display_order' => 25,
                'description' => '1% du salaire imposable',
            ],
        ];

        foreach ($items as $item) {
            PayrollItem::create($item);
        }

        $this->command->info('✅ Éléments de paie créés avec succès!');
    }
}
