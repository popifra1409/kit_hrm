<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Congé Annuel',
                'code' => 'CA',
                'description' => "Congé annuel : 30 jours pour les fonctionnaires, 18 jours pour les contractuels (+2 jours après 5 ans de service). Le nombre de jours exact est calculé automatiquement par employé.",
                'default_days' => 30, // valeur de base (fonctionnaires) ; le service d'attribution ajuste selon le statut
                'max_days_per_year' => null,
                'requires_document' => false,
                'is_paid' => true,
                'deductible_from_annual' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Congé Rayon X',
                'code' => 'CRX',
                'description' => "Congé spécifique aux employés du service de Radiologie : 30 jours par an.",
                'default_days' => 30,
                'max_days_per_year' => 30,
                'requires_document' => false,
                'is_paid' => true,
                'deductible_from_annual' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Congé de Maternité',
                'code' => 'CMAT',
                'description' => "Congé de maternité : 14 semaines (98 jours).",
                'default_days' => 98,
                'max_days_per_year' => 98,
                'requires_document' => true,
                'is_paid' => true,
                'deductible_from_annual' => false,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }

        echo "✅ " . count($types) . " types de congés créés/mis à jour.\n";
    }
}
