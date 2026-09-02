<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TradeBody;
use App\Models\Qualification;
use App\Models\JobTitle;

/**
 * ⚠️ SEEDER DE CORRECTION DE DONNÉES — spécifique aux bases existantes qui ont
 * encore des employés rattachés à l'ancienne table `positions`.
 *
 * - Ne fait RIEN d'utile sur une base neuve sans employés (juste un résumé "0 mis à jour").
 * - Ne supprime rien (ni positions, ni employees.position_id).
 * - Idempotent : peut être relancé sans dupliquer les TradeBody/Qualification (firstOrCreate).
 *
 * Pré-requis : TradeBodySeeder doit avoir tourné avant (pour que les 20 corps de
 * métier de base existent déjà) — sinon les entrées correspondantes seront ignorées
 * avec un ⚠️ affiché, et il suffira de relancer ce seeder après TradeBodySeeder.
 *
 * Usage : php artisan db:seed --class=Database\\Seeders\\MigratePositionsToQualificationsSeeder
 */
class MigratePositionsToQualificationsSeeder extends Seeder
{
    // 10 positions qui sont en réalité des rangs hiérarchiques (JobTitle), pas des métiers
    private array $jobTitleMap = [
        10 => 'Chef de Département/Sous-Direction', // Sous-Directeur des Finances et de la Comptabilité
        13 => 'Directeur Général',
        14 => 'Directeur Général Adjoint',
        15 => 'Directeur', // Directeur Administratif et Financier
        16 => 'Directeur', // Directeur des Ressources Humaines
        17 => 'Chef de Service',
        18 => 'Chef de Département/Sous-Direction', // Sous-Directeur (générique)
        40 => "Président du Conseil d'Administration",
        59 => 'Major',
        81 => 'Directeur', // DIRECTEUR DES RESSOURCES HUMAINES ET FINANCIERES
    ];

    // Nouveaux corps de métier à créer (absents des 20 actuels)
    private array $newTradeBodies = [
        ['name' => 'Kinésithérapeute', 'code' => 'KINE', 'category' => 'medical', 'description' => 'Kinésithérapeute diplômé'],
        ['name' => 'Informaticien', 'code' => 'INFO', 'category' => 'technical', 'description' => 'Informaticien / Ingénieur informatique'],
        ['name' => 'Aide-Soignant', 'code' => 'AIDE-SOIGNANT', 'category' => 'medical', 'description' => 'Aide-soignant'],
        ['name' => 'Caissier', 'code' => 'CAISSIER', 'category' => 'administrative', 'description' => 'Caissier(ère)'],
        ['name' => 'Couturier', 'code' => 'COUTURE', 'category' => 'support', 'description' => 'Tailleur / Couturier(ère)'],
        ['name' => 'Menuisier', 'code' => 'MENUIS', 'category' => 'technical', 'description' => 'Menuisier'],
        ['name' => 'Assistant Affaires Sociales', 'code' => 'ASSIST-SOC', 'category' => 'administrative', 'description' => 'Assistant(e) des Affaires Sociales'],
        ['name' => 'Agent de Morgue', 'code' => 'MORGUE', 'category' => 'support', 'description' => 'Morguier'],
        ['name' => 'Ingénieur Biomédical', 'code' => 'BIOMED', 'category' => 'technical', 'description' => 'Ingénieur(e) Biomédical(e)'],
        ['name' => 'Technicien Pharmacie', 'code' => 'TECH-PHARM', 'category' => 'technical', 'description' => 'Technicien(ne) en Pharmacie'],
        ['name' => 'Technicien Médico-Sanitaire', 'code' => 'TECH-MEDSAN', 'category' => 'technical', 'description' => 'Technicien(ne) Médico-Sanitaire / ATMS'],
        // ⚠️ Panier générique pour les fonctions administratives non spécialisées.
        ['name' => 'Agent Administratif Généraliste', 'code' => 'ADMIN-GEN', 'category' => 'administrative', 'description' => 'Fonctions administratives génériques'],
    ];

    // position_id => [trade_body_code existant ou nouveau, nom de la qualification]
    private array $qualificationMap = [
        // === Médecin (MED) ===
        1 => ['MED', 'Médecin Généraliste'],
        2 => ['MED', 'Médecin Spécialiste'],
        3 => ['MED', 'Chirurgien'],
        9 => ['MED', 'Radiologue'], // ⚠️ à vérifier : médecin ou technicien ?
        11 => ['MED', 'Anesthésiste'],
        33 => ['MED', 'Médecin'],
        51 => ['MED', 'Médecin Radiologue'],
        56 => ['MED', 'Médecin Gynécologue'],
        63 => ['MED', 'Médecin Spécialisé en Chirurgie Buccale'],
        65 => ['MED', 'Médecin Spécialisé en Radiologie'],
        66 => ['MED', 'Chirurgien Dentiste'],
        68 => ['MED', 'Médecin Bucco-Dentaire'],
        72 => ['MED', 'Médecin Biologiste'],
        74 => ['MED', 'Médecin Ophtalmologue'],

        // === Infirmier (INF) ===
        4 => ['INF', "Infirmier Diplômé d'État (IDE)"],
        5 => ['INF', 'Infirmier(ère) Breveté(e)'],
        47 => ['INF', 'Infirmier(ère)'],
        48 => ['INF', 'Infirmier(e) Spécialisé(e) en Ophtalmologie'],
        49 => ['INF', 'Infirmier (ère) Breveté (e) Généraliste'],
        53 => ['INF', 'Infirmier(ère) Assimilé(e)'],
        54 => ['INF', 'Infirmier(ère) Supérieur(e)'],
        58 => ['INF', "Infirmier(ère) Diplômé(e) d'État Principal(e)"],
        62 => ['INF', 'Infirmier(ère) Ophtalmologiste'],
        64 => ['INF', 'Infirmier(ère) Supérieur(e)'], // doublon de 54, fusionné automatiquement
        67 => ['INF', 'Infirmier(ère) Breveté(e) Accoucheur(euse)'],
        71 => ['INF', 'Infirmier(ère) Spécialisé(e) en Anesthésie et Réanimation'],
        75 => ['INF', 'Infirmier (ère) Adjoint(e)-Accoucheur (euse)'],
        83 => ['INF', 'Infirmier(ère) Spécialisé(e) en Santé de Reproduction'],

        // === Sage-Femme (SF) ===
        7 => ['SF', 'Sage-Femme'],

        // === Pharmacien (PHARM) ===
        70 => ['PHARM', 'Pharmacien(ne)'],
        73 => ['PHARM', 'Pharmacien(ne) Biologiste'],

        // === Technicien Laborantin (TECH-LAB) ===
        8 => ['TECH-LAB', 'Technicien de Laboratoire'],
        22 => ['TECH-LAB', 'Technicien(ne) Adjoint(e) de Laboratoire'],

        // === Technicien Maintenance (TECH-MAINT) ===
        29 => ['TECH-MAINT', 'Technicien de Maintenance'],

        // === Electricien (ELEC) ===
        39 => ['ELEC', 'Electricien'],
        80 => ['ELEC', 'Electrotechnicien'],

        // === Plombier (PLOMB) ===
        36 => ['PLOMB', 'Plombier'],

        // === Secrétaire (SEC) ===
        20 => ['SEC', 'Secrétaire'],

        // === Comptable (COMPT) ===
        21 => ['COMPT', 'Comptable'],
        57 => ['COMPT', 'Cadre Comptable'],

        // === Chauffeur (CHAUF) ===
        27 => ['CHAUF', 'Chauffeur'],

        // === Agent de Surface (SURF) ===
        25 => ['SURF', "Agent d'Entretien"],
        26 => ['SURF', 'Agent Domestique'], // ⚠️ à vérifier

        // === Gardien/Portier (GARD) ===
        30 => ['GARD', 'Gardien'],

        // === Agent de Sécurité (SEC-AGT) ===
        24 => ['SEC-AGT', 'Agent de Sécurité'],
        32 => ['SEC-AGT', 'Vigile'],

        // === Aide Cuisinier (AIDE-CUIS) ===
        28 => ['AIDE-CUIS', 'Agent de Cuisine'],

        // === Aide de Service (AIDE-SERV) ===
        31 => ['AIDE-SERV', 'Fille de Salle'],
        34 => ['AIDE-SERV', 'Garçon de Salle'],
        35 => ['AIDE-SERV', 'Brancardier'],

        // === Nouveaux corps de métier ===
        12 => ['KINE', 'Kinésithérapeute'],
        38 => ['INFO', 'Informaticien'],
        60 => ['INFO', 'Ingénieur des Travaux Informatiques'],
        6 => ['AIDE-SOIGNANT', 'Aide-Soignant'],
        69 => ['CAISSIER', 'Caissier(ère)'],
        76 => ['COUTURE', 'Tailleur'],
        78 => ['COUTURE', 'Couturière'],
        82 => ['MENUIS', 'Menuisier'],
        77 => ['ASSIST-SOC', 'Assistante des Affaires Sociales'],
        37 => ['MORGUE', 'Morguier'],
        41 => ['BIOMED', 'Ingénieur(e) Biomédical(e)'],
        50 => ['TECH-PHARM', 'Technicien(ne) Principal(e) en Pharmacie'],
        46 => ['TECH-MEDSAN', 'Technicien Médico-Sanitaire'],
        52 => ['TECH-MEDSAN', 'Technicien(ne) Médico-Sanitaire'], // doublon de 46, fusionné
        61 => ['TECH-MEDSAN', 'Agent Technique Médico-Sanitaire (ATMS)'],
        55 => ['TECH-MEDSAN', 'Technicien(ne) Supérieur(e)'], // ⚠️ à vérifier : générique

        // === Panier administratif générique (ADMIN-GEN) — ⚠️ à vérifier ===
        19 => ['ADMIN-GEN', "Agent Contractuel d'Administration"],
        23 => ['ADMIN-GEN', 'Employé de Bureau'],
        42 => ['ADMIN-GEN', 'Chef de Bureau'], // ⚠️ pourrait plutôt être un rang JobTitle
        43 => ['ADMIN-GEN', "Cadre Contractuel d'Administration"],
        44 => ['ADMIN-GEN', 'Agent Décisionnaire'],
        45 => ['ADMIN-GEN', 'Agent Décisionnaire'], // doublon de 44, fusionné
        79 => ['ADMIN-GEN', 'Agent de Bureau'],
    ];

    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('positions')) {
            echo "ℹ️  Table 'positions' absente (base neuve ou déjà nettoyée) — rien à migrer.\n";
            return;
        }

        // 1. Créer les nouveaux corps de métier manquants
        foreach ($this->newTradeBodies as $tb) {
            TradeBody::firstOrCreate(
                ['code' => $tb['code']],
                [
                    'name' => $tb['name'],
                    'description' => $tb['description'],
                    'category' => $tb['category'],
                    'is_active' => true,
                ]
            );
        }

        // 2. Créer/retrouver les Qualifications, et construire position_id => qualification_id
        $positionToQualification = [];
        $positionToTradeBody = [];

        foreach ($this->qualificationMap as $positionId => [$tradeBodyCode, $qualificationName]) {
            $tradeBody = TradeBody::where('code', $tradeBodyCode)->first();

            if (!$tradeBody) {
                echo "⚠️  TradeBody introuvable pour le code {$tradeBodyCode} (position #{$positionId}) — avez-vous lancé TradeBodySeeder ? Ignoré.\n";
                continue;
            }

            $qualification = Qualification::firstOrCreate(
                [
                    'trade_body_id' => $tradeBody->id,
                    'name' => $qualificationName,
                ],
                [
                    'code' => strtoupper($tradeBodyCode) . '-' . $positionId,
                    'is_active' => true,
                ]
            );

            $positionToQualification[$positionId] = $qualification->id;
            $positionToTradeBody[$positionId] = $tradeBody->id;
        }

        // 3. Construire position_id => job_title_id
        $positionToJobTitle = [];
        foreach ($this->jobTitleMap as $positionId => $jobTitleName) {
            $jobTitle = JobTitle::where('name', $jobTitleName)->first();

            if (!$jobTitle) {
                echo "⚠️  JobTitle introuvable : {$jobTitleName} (position #{$positionId}) — avez-vous lancé le seeder de JobTitle ? Ignoré.\n";
                continue;
            }

            $positionToJobTitle[$positionId] = $jobTitle->id;
        }

        // 4. Mettre à jour les employés (base vide => boucle ne fait rien, aucune erreur)
        $updatedQualification = 0;
        $updatedJobTitle = 0;
        $unmapped = 0;

        DB::table('employees')->whereNotNull('position_id')->orderBy('id')
            ->chunkById(200, function ($employees) use (
                $positionToQualification,
                $positionToTradeBody,
                $positionToJobTitle,
                &$updatedQualification,
                &$updatedJobTitle,
                &$unmapped
            ) {
                foreach ($employees as $employee) {
                    $positionId = $employee->position_id;

                    if (isset($positionToQualification[$positionId])) {
                        DB::table('employees')->where('id', $employee->id)->update([
                            'qualification_id' => $positionToQualification[$positionId],
                            'trade_body_id' => $positionToTradeBody[$positionId],
                        ]);
                        $updatedQualification++;
                    } elseif (isset($positionToJobTitle[$positionId])) {
                        DB::table('employees')->where('id', $employee->id)->update([
                            'job_title_id' => $positionToJobTitle[$positionId],
                        ]);
                        $updatedJobTitle++;
                    } else {
                        $unmapped++;
                        echo "⚠️  Employé #{$employee->id} (matricule {$employee->matricule}) : position_id {$positionId} non mappé.\n";
                    }
                }
            });

        echo "\n✅ Migration de données terminée :\n";
        echo "   - {$updatedQualification} employés mis à jour vers qualification_id/trade_body_id\n";
        echo "   - {$updatedJobTitle} employés mis à jour vers job_title_id\n";
        echo "   - {$unmapped} employés non mappés (à vérifier manuellement)\n";
        echo "\n⚠️  position_id et la table positions n'ont PAS été supprimés. Vérifiez le résultat avant de le faire.\n";
    }
}
