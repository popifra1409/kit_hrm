<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcurementType;

class ProcurementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Travaux',
                'code' => 'TRV',
                'category' => 'works',
                'description' => 'Marchés de travaux (construction, réhabilitation, etc.)',
                'threshold_aon' => 50000000, // 50M FCFA pour AON
                'threshold_aoi' => 500000000, // 500M FCFA pour AOI
                'requires_armp_approval' => true,
                'min_publication_days' => 30,
            ],
            [
                'name' => 'Fournitures',
                'code' => 'FRN',
                'category' => 'goods',
                'description' => 'Fournitures de biens et équipements',
                'threshold_aon' => 30000000, // 30M FCFA
                'threshold_aoi' => 300000000, // 300M FCFA
                'requires_armp_approval' => true,
                'min_publication_days' => 21,
            ],
            [
                'name' => 'Services Courants',
                'code' => 'SVC',
                'category' => 'services',
                'description' => 'Prestations de services courants',
                'threshold_aon' => 20000000, // 20M FCFA
                'threshold_aoi' => 200000000, // 200M FCFA
                'requires_armp_approval' => true,
                'min_publication_days' => 21,
            ],
            [
                'name' => 'Services de Consultants',
                'code' => 'CST',
                'category' => 'consulting',
                'description' => 'Prestations intellectuelles et conseils',
                'threshold_aon' => 20000000,
                'threshold_aoi' => 200000000,
                'requires_armp_approval' => true,
                'min_publication_days' => 21,
            ],
        ];

        foreach ($types as $type) {
            ProcurementType::create($type);
        }

        $this->command->info('✅ Types de marchés créés avec succès!');
    }
}
