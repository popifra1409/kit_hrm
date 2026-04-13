<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'CAMTEL',
                'registration_number' => 'RC/YAO/2000/B/123',
                'tax_number' => 'M012345678901',
                'armp_number' => 'ARMP-2020-001',
                'supplier_type' => 'company',
                'category' => 'services',
                'address' => 'Boulevard du 20 Mai, Yaoundé',
                'city' => 'Yaoundé',
                'phone' => '+237222223344',
                'email' => 'contact@camtel.cm',
                'contact_person' => 'Jean MBARGA',
                'contact_phone' => '+237677889900',
                'status' => 'active',
            ],
            [
                'name' => 'PHARMAQUICK SARL',
                'registration_number' => 'RC/DLA/2015/B/456',
                'tax_number' => 'M987654321098',
                'armp_number' => 'ARMP-2018-045',
                'supplier_type' => 'company',
                'category' => 'goods',
                'address' => 'Rue de la Pharmacie, Douala',
                'city' => 'Douala',
                'phone' => '+237233334455',
                'email' => 'info@pharmaquick.cm',
                'contact_person' => 'Marie EKEME',
                'contact_phone' => '+237699887766',
                'specialties' => json_encode(['Médicaments', 'Matériel médical', 'Consommables']),
                'status' => 'active',
            ],
            [
                'name' => 'BTP EXCELLENCE',
                'registration_number' => 'RC/YAO/2010/B/789',
                'tax_number' => 'M456789012345',
                'armp_number' => 'ARMP-2019-120',
                'supplier_type' => 'company',
                'category' => 'works',
                'address' => 'Quartier Bastos, Yaoundé',
                'city' => 'Yaoundé',
                'phone' => '+237222445566',
                'email' => 'contact@btpexcellence.cm',
                'contact_person' => 'Paul NKOA',
                'contact_phone' => '+237677665544',
                'specialties' => json_encode(['Construction', 'Réhabilitation', 'VRD']),
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        $this->command->info('✅ Fournisseurs créés avec succès!');
    }
}
