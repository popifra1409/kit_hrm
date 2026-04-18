<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionHierarchySeeder extends Seeder
{
    /**
     * Créer les positions types avec leurs niveaux hiérarchiques
     */
    public function run(): void
    {
        $this->command->info('🎯 Création des positions hiérarchiques...');

        $positions = [
            // ========================================
            // NIVEAU EXÉCUTIF (1-3)
            // ========================================
            [
                'name' => 'Président du Conseil d\'Administration',
                'code' => 'PCA',
                'hierarchical_level' => 'pca',
                'level_rank' => 1,
                'description' => 'Président du Conseil d\'Administration',
                'is_managerial' => true,
            ],
            [
                'name' => 'Directeur Général',
                'code' => 'DG',
                'hierarchical_level' => 'dg',
                'level_rank' => 2,
                'description' => 'Directeur Général de l\'établissement',
                'is_managerial' => true,
            ],
            [
                'name' => 'Directeur Général Adjoint',
                'code' => 'DGA',
                'hierarchical_level' => 'dga',
                'level_rank' => 3,
                'description' => 'Directeur Général Adjoint',
                'is_managerial' => true,
            ],

            // ========================================
            // NIVEAU DIRECTION (4)
            // ========================================
            [
                'name' => 'Directeur des Ressources Humaines',
                'code' => 'DRH',
                'hierarchical_level' => 'directeur',
                'level_rank' => 4,
                'description' => 'Directeur d\'une direction',
                'is_managerial' => true,
            ],
            [
                'name' => 'Directeur Administratif et Financier',
                'code' => 'DAF',
                'hierarchical_level' => 'directeur',
                'level_rank' => 4,
                'description' => 'Directeur d\'une direction',
                'is_managerial' => true,
            ],
            [
                'name' => 'Directeur des Affaires Médicales',
                'code' => 'DAM',
                'hierarchical_level' => 'directeur',
                'level_rank' => 4,
                'description' => 'Directeur d\'une direction',
                'is_managerial' => true,
            ],

            // ========================================
            // NIVEAU SOUS-DIRECTION (5)
            // ========================================
            [
                'name' => 'Sous-Directeur',
                'code' => 'SDIR',
                'hierarchical_level' => 'sous_directeur',
                'level_rank' => 5,
                'description' => 'Sous-Directeur d\'une sous-direction administrative',
                'is_managerial' => true,
            ],
            [
                'name' => 'Chef de Département Médical',
                'code' => 'CDM',
                'hierarchical_level' => 'sous_directeur',
                'level_rank' => 5,
                'description' => 'Chef de Département Médical (équivalent Sous-Directeur)',
                'is_managerial' => true,
            ],

            // ========================================
            // NIVEAU SERVICE (6)
            // ========================================
            [
                'name' => 'Chef de Service Médical',
                'code' => 'CSM',
                'hierarchical_level' => 'chef_service',
                'level_rank' => 6,
                'description' => 'Chef de Service Médical',
                'is_managerial' => true,
            ],
            [
                'name' => 'Chef de Service Administratif',
                'code' => 'CSA',
                'hierarchical_level' => 'chef_service',
                'level_rank' => 6,
                'description' => 'Chef de Service Administratif',
                'is_managerial' => true,
            ],

            // ========================================
            // NIVEAU SECTEUR (7)
            // ========================================
            [
                'name' => 'Major',
                'code' => 'MAJ',
                'hierarchical_level' => 'major',
                'level_rank' => 7,
                'description' => 'Major d\'un service ou secteur médical',
                'is_managerial' => true,
            ],
            [
                'name' => 'Chef de Secteur',
                'code' => 'CSECT',
                'hierarchical_level' => 'chef_secteur',
                'level_rank' => 7,
                'description' => 'Chef de Secteur ou d\'Unité',
                'is_managerial' => true,
            ],

            // ========================================
            // POSTES MÉDICAUX (8)
            // ========================================
            [
                'name' => 'Médecin Spécialiste',
                'code' => 'MEDSPE',
                'hierarchical_level' => 'cadre',
                'level_rank' => 8,
                'description' => 'Médecin Spécialiste',
                'is_managerial' => false,
            ],
            [
                'name' => 'Médecin Généraliste',
                'code' => 'MEDGEN',
                'hierarchical_level' => 'cadre',
                'level_rank' => 8,
                'description' => 'Médecin Généraliste',
                'is_managerial' => false,
            ],
            [
                'name' => 'Infirmier(ère) Diplômé(e) d\'État',
                'code' => 'IDE',
                'hierarchical_level' => 'cadre',
                'level_rank' => 8,
                'description' => 'Infirmier Diplômé d\'État',
                'is_managerial' => false,
            ],
            [
                'name' => 'Sage-Femme',
                'code' => 'SF',
                'hierarchical_level' => 'cadre',
                'level_rank' => 8,
                'description' => 'Sage-Femme',
                'is_managerial' => false,
            ],

            // ========================================
            // POSTES PARAMÉDICAUX (8-9)
            // ========================================
            [
                'name' => 'Aide-Soignant(e)',
                'code' => 'AS',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Aide-Soignant',
                'is_managerial' => false,
            ],
            [
                'name' => 'Brancardier',
                'code' => 'BRANC',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Brancardier',
                'is_managerial' => false,
            ],

            // ========================================
            // POSTES ADMINISTRATIFS (8-9)
            // ========================================
            [
                'name' => 'Responsable Administratif',
                'code' => 'RESPADM',
                'hierarchical_level' => 'cadre',
                'level_rank' => 8,
                'description' => 'Responsable Administratif',
                'is_managerial' => true,
            ],
            [
                'name' => 'Assistant(e) Administratif(ve)',
                'code' => 'ASSADM',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Assistant Administratif',
                'is_managerial' => false,
            ],
            [
                'name' => 'Secrétaire',
                'code' => 'SEC',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Secrétaire',
                'is_managerial' => false,
            ],

            // ========================================
            // POSTES SUPPORT (9)
            // ========================================
            [
                'name' => 'Agent d\'Entretien',
                'code' => 'AGENTR',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Agent d\'Entretien',
                'is_managerial' => false,
            ],
            [
                'name' => 'Agent de Sécurité',
                'code' => 'AGSEC',
                'hierarchical_level' => 'agent',
                'level_rank' => 9,
                'description' => 'Agent de Sécurité',
                'is_managerial' => false,
            ],

            // ========================================
            // STAGIAIRES (10)
            // ========================================
            [
                'name' => 'Stagiaire',
                'code' => 'STAG',
                'hierarchical_level' => 'stagiaire',
                'level_rank' => 10,
                'description' => 'Stagiaire',
                'is_managerial' => false,
            ],
        ];

        $count = 0;
        foreach ($positions as $positionData) {
            Position::updateOrCreate(
                ['code' => $positionData['code']],
                $positionData
            );
            $count++;
            $this->command->line("  ✓ {$positionData['name']} (Niveau {$positionData['level_rank']})");
        }

        $this->command->info("✅ {$count} positions hiérarchiques créées !");
    }
}
