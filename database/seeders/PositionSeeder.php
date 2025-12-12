<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            // Personnel Soignant
            ['name' => 'Médecin Généraliste', 'code' => 'MG', 'description' => 'Médecin de médecine générale'],
            ['name' => 'Médecin Spécialiste', 'code' => 'MS', 'description' => 'Médecin spécialiste'],
            ['name' => 'Chirurgien', 'code' => 'CHIR', 'description' => 'Chirurgien'],
            ['name' => 'Infirmier Diplômé d\'État (IDE)', 'code' => 'IDE', 'description' => 'Infirmier diplômé'],
            ['name' => 'Infirmier Breveté', 'code' => 'IB', 'description' => 'Infirmier breveté'],
            ['name' => 'Aide-Soignant', 'code' => 'AS', 'description' => 'Aide-soignant'],
            ['name' => 'Sage-Femme', 'code' => 'SF', 'description' => 'Sage-femme'],
            ['name' => 'Technicien de Laboratoire', 'code' => 'TL', 'description' => 'Technicien de laboratoire'],
            ['name' => 'Radiologue', 'code' => 'RAD', 'description' => 'Radiologue'],
            ['name' => 'Pharmacien', 'code' => 'PHARM', 'description' => 'Pharmacien'],
            ['name' => 'Anesthésiste', 'code' => 'ANESTH', 'description' => 'Médecin anesthésiste'],
            ['name' => 'Kinésithérapeute', 'code' => 'KINE', 'description' => 'Kinésithérapeute'],

            // Personnel Administratif
            ['name' => 'Directeur Général', 'code' => 'DG', 'description' => 'Directeur général'],
            ['name' => 'Directeur Général Adjoint', 'code' => 'DGA', 'description' => 'Directeur général adjoint'],
            ['name' => 'Directeur Administratif et Financier', 'code' => 'DAF', 'description' => 'Directeur administratif et financier'],
            ['name' => 'Directeur des Ressources Humaines', 'code' => 'DRH', 'description' => 'Directeur des ressources humaines'],
            ['name' => 'Chef de Service', 'code' => 'CS', 'description' => 'Chef de service'],
            ['name' => 'Sous-Directeur', 'code' => 'SDIR', 'description' => 'Sous-directeur'],
            ['name' => 'Agent Administratif', 'code' => 'AA', 'description' => 'Agent administratif'],
            ['name' => 'Secrétaire', 'code' => 'SEC', 'description' => 'Secrétaire'],
            ['name' => 'Comptable', 'code' => 'COMPT', 'description' => 'Comptable'],
            ['name' => 'Caissier', 'code' => 'CAISS', 'description' => 'Caissier'],
            ['name' => 'Employé de Bureau', 'code' => 'EB', 'description' => 'Employé de bureau'],

            // Personnel de Support
            ['name' => 'Agent de Sécurité', 'code' => 'SECU', 'description' => 'Agent de sécurité'],
            ['name' => 'Agent d\'Entretien', 'code' => 'ENTRET', 'description' => 'Agent d\'entretien'],
            ['name' => 'Agent Domestique', 'code' => 'DOM', 'description' => 'Agent domestique'],
            ['name' => 'Chauffeur', 'code' => 'CHAUF', 'description' => 'Chauffeur'],
            ['name' => 'Agent de Cuisine', 'code' => 'CUIS', 'description' => 'Agent de cuisine'],
            ['name' => 'Technicien de Maintenance', 'code' => 'MAINT', 'description' => 'Technicien de maintenance'],
            ['name' => 'Gardien', 'code' => 'GARD', 'description' => 'Gardien'],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }

        $this->command->info('✅ ' . count($positions) . ' postes créés avec succès!');
    }
}
