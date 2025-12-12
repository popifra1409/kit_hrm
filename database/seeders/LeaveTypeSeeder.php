<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Congé Annuel',
                'code' => 'CA',
                'description' => 'Congé annuel payé - Droit : 30 jours ouvrables par an',
                'default_days' => 30,
                'max_days_per_year' => 30,
                'requires_document' => false,
                'is_paid' => true,
                'deductible_from_annual' => false,
            ],
            [
                'name' => 'Congé de Maladie',
                'code' => 'CM',
                'description' => 'Congé pour raison médicale avec certificat médical',
                'default_days' => 15,
                'max_days_per_year' => 90,
                'requires_document' => true,
                'is_paid' => true,
                'deductible_from_annual' => false,
            ],
            [
                'name' => 'Congé de Maternité',
                'code' => 'CMAT',
                'description' => 'Congé de maternité - 14 semaines',
                'default_days' => 98, // 14 semaines = 98 jours
                'max_days_per_year' => 98,
                'requires_document' => true,
                'is_paid' => true,
                'deductible_from_annual' => false,
            ],
            [
                'name' => 'Congé de Paternité',
                'code' => 'CPAT',
                'description' => 'Congé de paternité - 10 jours',
                'default_days' => 10,
                'max_days_per_year' => 10,
                'requires_document' => true,
                'is_paid' => true,
                'deductible_from_annual' => false,
            ],
            [
                'name' => 'Congé pour Événement Familial',
                'code' => 'CEF',
                'description' => 'Mariage, décès, etc.',
                'default_days' => 3,
                'max_days_per_year' => 10,
                'requires_document' => true,
                'is_paid' => true,
                'deductible_from_annual' => false,
            ],
            [
                'name' => 'Permission Exceptionnelle',
                'code' => 'PE',
                'description' => 'Permission pour raison personnelle (déductible du congé annuel)',
                'default_days' => 1,
                'max_days_per_year' => 5,
                'requires_document' => false,
                'is_paid' => true,
                'deductible_from_annual' => true,
            ],
            [
                'name' => 'Congé sans Solde',
                'code' => 'CSS',
                'description' => 'Congé non payé à la demande de l\'employé',
                'default_days' => 0,
                'max_days_per_year' => 90,
                'requires_document' => false,
                'is_paid' => false,
                'deductible_from_annual' => false,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::create($type);
        }

        $this->command->info('✅ Types de congés créés avec succès!');
    }
}
