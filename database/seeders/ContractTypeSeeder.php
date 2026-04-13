<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractType;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $contractTypes = [
            [
                'name' => 'CDI',
                'code' => 'CDI',
                'description' => 'Contrat à Durée Indéterminée',
                'requires_end_date' => false,
                'max_duration_months' => null,
                'renewable' => false,
            ],
            [
                'name' => 'CDD',
                'code' => 'CDD',
                'description' => 'Contrat à Durée Déterminée',
                'requires_end_date' => true,
                'max_duration_months' => 24,
                'renewable' => true,
            ],
            [
                'name' => 'Temporaire',
                'code' => 'TEMP',
                'description' => 'Contrat Temporaire',
                'requires_end_date' => true,
                'max_duration_months' => 6,
                'renewable' => true,
            ],
            [
                'name' => 'Vacataire',
                'code' => 'VAC',
                'description' => 'Contrat Vacataire (missions ponctuelles)',
                'requires_end_date' => true,
                'max_duration_months' => 3,
                'renewable' => true,
            ],
            [
                'name' => 'Stage',
                'code' => 'STAGE',
                'description' => 'Convention de Stage',
                'requires_end_date' => true,
                'max_duration_months' => 6,
                'renewable' => false,
            ],
        ];

        foreach ($contractTypes as $type) {
            ContractType::create($type);
        }

        echo "✅ Types de contrat créés!\n";
    }
}
