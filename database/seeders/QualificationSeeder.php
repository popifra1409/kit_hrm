<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Qualification;
use App\Models\TradeBody;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $qualifications = [
            // === QUALIFICATIONS MÉDICALES ===
            ['trade_body_code' => 'MED', 'name' => 'Médecin Généraliste', 'code' => 'MED-GEN', 'level_rank' => 1],
            ['trade_body_code' => 'MED', 'name' => 'Médecin Spécialiste', 'code' => 'MED-SPEC', 'level_rank' => 2],
            ['trade_body_code' => 'MED', 'name' => 'Chirurgien', 'code' => 'CHIR', 'level_rank' => 2],
            ['trade_body_code' => 'MED', 'name' => 'Pédiatre', 'code' => 'PEDI', 'level_rank' => 2],

            ['trade_body_code' => 'INF', 'name' => 'Infirmier Diplômé d\'État', 'code' => 'INF-IDE', 'level_rank' => 1],
            ['trade_body_code' => 'INF', 'name' => 'Infirmier Spécialisé', 'code' => 'INF-SPEC', 'level_rank' => 2],

            ['trade_body_code' => 'SF', 'name' => 'Sage-Femme Diplômée', 'code' => 'SF-DIPL', 'level_rank' => 1],

            ['trade_body_code' => 'PHARM', 'name' => 'Pharmacien', 'code' => 'PHARM-DIPL', 'level_rank' => 1],

            // === QUALIFICATIONS TECHNIQUES ===
            ['trade_body_code' => 'TECH-LAB', 'name' => 'Technicien Laborantin', 'code' => 'TECH-LAB-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'TECH-LAB', 'name' => 'Technicien Laborantin Spécialisé', 'code' => 'TECH-LAB-SPEC', 'level_rank' => 2],

            ['trade_body_code' => 'TECH-RAD', 'name' => 'Technicien Radiologie', 'code' => 'TECH-RAD-DIPL', 'level_rank' => 1],

            ['trade_body_code' => 'TECH-MAINT', 'name' => 'Technicien Maintenance', 'code' => 'TECH-MAINT-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'ELEC', 'name' => 'Électricien', 'code' => 'ELEC-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'PLOMB', 'name' => 'Plombier', 'code' => 'PLOMB-DIPL', 'level_rank' => 1],

            // === QUALIFICATIONS ADMINISTRATIVES ===
            ['trade_body_code' => 'SEC', 'name' => 'Secrétaire de Direction', 'code' => 'SEC-DIR', 'level_rank' => 2],
            ['trade_body_code' => 'SEC', 'name' => 'Secrétaire Administratif', 'code' => 'SEC-ADM', 'level_rank' => 1],

            ['trade_body_code' => 'COMPT', 'name' => 'Comptable', 'code' => 'COMPT-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'COMPT', 'name' => 'Comptable Confirmé', 'code' => 'COMPT-CONF', 'level_rank' => 2],

            ['trade_body_code' => 'RH', 'name' => 'Agent RH', 'code' => 'RH-AGT', 'level_rank' => 1],
            ['trade_body_code' => 'MAG', 'name' => 'Agent de Magasin', 'code' => 'MAG-AGT', 'level_rank' => 1],
            ['trade_body_code' => 'CHAUF', 'name' => 'Chauffeur', 'code' => 'CHAUF-DIPL', 'level_rank' => 1],

            // === QUALIFICATIONS SUPPORT ===
            ['trade_body_code' => 'SURF', 'name' => 'Agent de Surface', 'code' => 'SURF-AGT', 'level_rank' => 1],
            ['trade_body_code' => 'GARD', 'name' => 'Gardien', 'code' => 'GARD-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'SEC-AGT', 'name' => 'Agent de Sécurité', 'code' => 'SEC-AGT-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'CUIS', 'name' => 'Cuisinier', 'code' => 'CUIS-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'AIDE-CUIS', 'name' => 'Aide Cuisinier', 'code' => 'AIDE-CUIS-DIPL', 'level_rank' => 1],
            ['trade_body_code' => 'AIDE-SERV', 'name' => 'Aide de Service', 'code' => 'AIDE-SERV-DIPL', 'level_rank' => 1],
        ];

        $count = 0;
        foreach ($qualifications as $qual) {
            $tradeBody = TradeBody::where('code', $qual['trade_body_code'])->first();

            if ($tradeBody) {
                Qualification::firstOrCreate(
                    ['code' => $qual['code']],
                    [
                        'name' => $qual['name'],
                        'trade_body_id' => $tradeBody->id,
                        'level_rank' => $qual['level_rank'],
                        'is_active' => true,
                    ]
                );
                $count++;
            }
        }

        echo "✅ $count qualifications créées/mises à jour\n";
    }
}
