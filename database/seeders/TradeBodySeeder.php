<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TradeBody;

class TradeBodySeeder extends Seeder
{
    public function run(): void
    {
        $tradeBodies = [
            // === CORPS MÉDICAUX ===
            [
                'name' => 'Médecin',
                'code' => 'MED',
                'description' => 'Docteur en médecine',
                'category' => 'medical',
                'is_active' => true,
            ],
            [
                'name' => 'Infirmier',
                'code' => 'INF',
                'description' => 'Infirmier diplômé',
                'category' => 'medical',
                'is_active' => true,
            ],
            [
                'name' => 'Sage-Femme',
                'code' => 'SF',
                'description' => 'Sage-femme diplômée',
                'category' => 'medical',
                'is_active' => true,
            ],
            [
                'name' => 'Pharmacien',
                'code' => 'PHARM',
                'description' => 'Pharmacien diplômé',
                'category' => 'medical',
                'is_active' => true,
            ],

            // === CORPS TECHNIQUES ===
            [
                'name' => 'Technicien Laborantin',
                'code' => 'TECH-LAB',
                'description' => 'Technicien en laboratoire',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'name' => 'Technicien Radiologie',
                'code' => 'TECH-RAD',
                'description' => 'Technicien en radiologie',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'name' => 'Technicien Maintenance',
                'code' => 'TECH-MAINT',
                'description' => 'Technicien en maintenance',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'name' => 'Electricien',
                'code' => 'ELEC',
                'description' => 'Électricien',
                'category' => 'technical',
                'is_active' => true,
            ],
            [
                'name' => 'Plombier',
                'code' => 'PLOMB',
                'description' => 'Plombier',
                'category' => 'technical',
                'is_active' => true,
            ],

            // === CORPS ADMINISTRATIFS ===
            [
                'name' => 'Secrétaire',
                'code' => 'SEC',
                'description' => 'Secrétaire administratif',
                'category' => 'administrative',
                'is_active' => true,
            ],
            [
                'name' => 'Comptable',
                'code' => 'COMPT',
                'description' => 'Comptable',
                'category' => 'administrative',
                'is_active' => true,
            ],
            [
                'name' => 'Agent Ressources Humaines',
                'code' => 'RH',
                'description' => 'Agent en ressources humaines',
                'category' => 'administrative',
                'is_active' => true,
            ],
            [
                'name' => 'Agent de Magasin',
                'code' => 'MAG',
                'description' => 'Agent de magasin/Stockiste',
                'category' => 'administrative',
                'is_active' => true,
            ],
            [
                'name' => 'Chauffeur',
                'code' => 'CHAUF',
                'description' => 'Chauffeur',
                'category' => 'administrative',
                'is_active' => true,
            ],

            // === CORPS DE SUPPORT ===
            [
                'name' => 'Agent de Surface',
                'code' => 'SURF',
                'description' => 'Agent de nettoyage et hygiène',
                'category' => 'support',
                'is_active' => true,
            ],
            [
                'name' => 'Gardien/Portier',
                'code' => 'GARD',
                'description' => 'Gardien ou portier',
                'category' => 'support',
                'is_active' => true,
            ],
            [
                'name' => 'Agent de Sécurité',
                'code' => 'SEC-AGT',
                'description' => 'Agent de sécurité',
                'category' => 'support',
                'is_active' => true,
            ],
            [
                'name' => 'Cuisinier',
                'code' => 'CUIS',
                'description' => 'Cuisinier',
                'category' => 'support',
                'is_active' => true,
            ],
            [
                'name' => 'Aide Cuisinier',
                'code' => 'AIDE-CUIS',
                'description' => 'Aide cuisinier',
                'category' => 'support',
                'is_active' => true,
            ],
            [
                'name' => 'Aide de Service',
                'code' => 'AIDE-SERV',
                'description' => 'Aide de service/Brancardier',
                'category' => 'support',
                'is_active' => true,
            ],
        ];

        foreach ($tradeBodies as $tradeBody) {
            TradeBody::firstOrCreate(
                ['code' => $tradeBody['code']],
                $tradeBody
            );
        }

        echo "✅ " . count($tradeBodies) . " corps de métier créés/mis à jour\n";
    }
}
