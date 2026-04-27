<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EvaluationCriterion;

class EvaluationCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            // COMPORTEMENT
            [
                'category' => 'comportement',
                'code' => 'assiduite',
                'name' => 'Assiduité et Ponctualité',
                'description' => 'Respect des horaires, présence régulière',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'order' => 1,
            ],
            [
                'category' => 'comportement',
                'code' => 'discipline',
                'name' => 'Discipline et Respect du Règlement',
                'description' => 'Respect du règlement intérieur et des procédures',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'order' => 2,
            ],
            [
                'category' => 'comportement',
                'code' => 'relations_collegues',
                'name' => 'Relations avec les Collègues',
                'description' => 'Esprit d\'équipe, collaboration, communication',
                'max_score' => 20,
                'weight' => 0.8,
                'applies_to' => 'all',
                'order' => 3,
            ],

            // COMPÉTENCE
            [
                'category' => 'competence',
                'code' => 'maitrise_poste',
                'name' => 'Maîtrise du Poste',
                'description' => 'Connaissances techniques et professionnelles',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'order' => 4,
            ],
            [
                'category' => 'competence',
                'code' => 'qualite_travail',
                'name' => 'Qualité du Travail',
                'description' => 'Précision, rigueur, fiabilité',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'order' => 5,
            ],
            [
                'category' => 'competence',
                'code' => 'autonomie',
                'name' => 'Autonomie et Initiative',
                'description' => 'Capacité à travailler seul et prendre des initiatives',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'order' => 6,
            ],

            // PERFORMANCE
            [
                'category' => 'performance',
                'code' => 'productivite',
                'name' => 'Productivité',
                'description' => 'Quantité de travail effectué',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'order' => 7,
            ],
            [
                'category' => 'performance',
                'code' => 'objectifs',
                'name' => 'Atteinte des Objectifs',
                'description' => 'Réalisation des objectifs fixés',
                'max_score' => 20,
                'weight' => 2.0,
                'applies_to' => 'all',
                'order' => 8,
            ],

            // SPÉCIFIQUE PERSONNEL SOIGNANT
            [
                'category' => 'comportement',
                'code' => 'relation_patients',
                'name' => 'Relation avec les Patients',
                'description' => 'Empathie, écoute, communication avec les patients',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'soignant',
                'order' => 9,
            ],
            [
                'category' => 'competence',
                'code' => 'hygiene_securite',
                'name' => 'Hygiène et Sécurité',
                'description' => 'Respect des protocoles d\'hygiène et de sécurité',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'soignant',
                'order' => 10,
            ],
        ];

        foreach ($criteria as $criterion) {
            EvaluationCriterion::updateOrCreate(
                ['code' => $criterion['code']],
                $criterion
            );
        }

        $this->command->info('✅ Critères d\'évaluation créés!');
    }
}
