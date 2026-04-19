<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuotpartParameter;
use App\Models\EvaluationCriterion;
use App\Models\QuotpartDeductionType;

class QuotpartSystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedParameters();
        $this->seedEvaluationCriteria();
        $this->seedDeductionTypes();
    }

    protected function seedParameters()
    {
        $this->command->info('📊 Création des paramètres de calcul...');

        $parameters = [
            // PARAMÈTRES DE BASE
            [
                'category' => 'base',
                'code' => 'indice_weight',
                'name' => 'Coefficient Indice',
                'description' => 'Poids de l\'indice dans le calcul des points de base',
                'applies_to' => 'all',
                'weight' => 1.0000,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'category' => 'base',
                'code' => 'anciennete_weight',
                'name' => 'Coefficient Ancienneté',
                'description' => 'Points par année d\'ancienneté',
                'applies_to' => 'all',
                'weight' => 0.5000,
                'is_active' => true,
                'order' => 2,
            ],

            // PARAMÈTRES PERFORMANCE
            [
                'category' => 'performance',
                'code' => 'evaluation_weight',
                'name' => 'Coefficient Évaluation',
                'description' => 'Poids des notes d\'évaluation',
                'applies_to' => 'all',
                'weight' => 2.0000,
                'is_active' => true,
                'order' => 3,
            ],

            // PARAMÈTRES MÉDICAUX
            [
                'category' => 'medical',
                'code' => 'consultation_weight',
                'name' => 'Points par Consultation',
                'description' => 'Nombre de points pour chaque consultation',
                'applies_to' => 'soignant',
                'weight' => 0.5000,
                'is_active' => true,
                'order' => 10,
            ],
            [
                'category' => 'medical',
                'code' => 'prescription_weight',
                'name' => 'Points par Prescription',
                'description' => 'Nombre de points pour chaque prescription',
                'applies_to' => 'soignant',
                'weight' => 0.3000,
                'is_active' => true,
                'order' => 11,
            ],
            [
                'category' => 'medical',
                'code' => 'acte_weight',
                'name' => 'Points par Acte Médical',
                'description' => 'Nombre de points pour chaque acte posé',
                'applies_to' => 'soignant',
                'weight' => 1.0000,
                'is_active' => true,
                'order' => 12,
            ],
            [
                'category' => 'medical',
                'code' => 'garde_weight',
                'name' => 'Points par Garde',
                'description' => 'Nombre de points pour chaque garde effectuée',
                'applies_to' => 'soignant',
                'weight' => 5.0000,
                'is_active' => true,
                'order' => 13,
            ],

            // PARAMÈTRES MANAGEMENT
            [
                'category' => 'management',
                'code' => 'directeur_bonus',
                'name' => 'Bonus Directeur',
                'description' => 'Bonus pour les directeurs',
                'applies_to' => 'all',
                'weight' => 20.0000,
                'is_active' => true,
                'order' => 20,
            ],
            [
                'category' => 'management',
                'code' => 'sous_directeur_bonus',
                'name' => 'Bonus Sous-Directeur',
                'description' => 'Bonus pour les sous-directeurs',
                'applies_to' => 'all',
                'weight' => 15.0000,
                'is_active' => true,
                'order' => 21,
            ],
            [
                'category' => 'management',
                'code' => 'chef_departement_bonus',
                'name' => 'Bonus Chef de Département',
                'description' => 'Bonus pour les chefs de département',
                'applies_to' => 'all',
                'weight' => 12.0000,
                'is_active' => true,
                'order' => 22,
            ],
            [
                'category' => 'management',
                'code' => 'chef_service_bonus',
                'name' => 'Bonus Chef de Service',
                'description' => 'Bonus pour les chefs de service',
                'applies_to' => 'all',
                'weight' => 10.0000,
                'is_active' => true,
                'order' => 23,
            ],
        ];

        foreach ($parameters as $param) {
            QuotpartParameter::updateOrCreate(
                ['code' => $param['code']],
                $param
            );
            $this->command->line("  ✓ {$param['name']}");
        }
    }

    protected function seedEvaluationCriteria()
    {
        $this->command->info('📋 Création des critères d\'évaluation...');

        $criteria = [
            // COMPORTEMENT
            [
                'category' => 'comportement',
                'code' => 'assiduite',
                'name' => 'Assiduité et Ponctualité',
                'description' => 'Présence régulière et respect des horaires',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'category' => 'comportement',
                'code' => 'discipline',
                'name' => 'Discipline et Respect du Règlement',
                'description' => 'Respect des règles internes',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'category' => 'comportement',
                'code' => 'relation_collegues',
                'name' => 'Relations avec les Collègues',
                'description' => 'Esprit d\'équipe et collaboration',
                'max_score' => 20,
                'weight' => 0.8,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 3,
            ],

            // COMPÉTENCE
            [
                'category' => 'competence',
                'code' => 'maitrise_poste',
                'name' => 'Maîtrise du Poste',
                'description' => 'Compétence technique et professionnelle',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 10,
            ],
            [
                'category' => 'competence',
                'code' => 'qualite_travail',
                'name' => 'Qualité du Travail',
                'description' => 'Précision et rigueur dans l\'exécution',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 11,
            ],
            [
                'category' => 'competence',
                'code' => 'autonomie',
                'name' => 'Autonomie et Initiative',
                'description' => 'Capacité à travailler de manière autonome',
                'max_score' => 20,
                'weight' => 1.0,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 12,
            ],

            // PERFORMANCE
            [
                'category' => 'performance',
                'code' => 'productivite',
                'name' => 'Productivité',
                'description' => 'Quantité et rapidité du travail',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 20,
            ],
            [
                'category' => 'performance',
                'code' => 'atteinte_objectifs',
                'name' => 'Atteinte des Objectifs',
                'description' => 'Réalisation des objectifs fixés',
                'max_score' => 20,
                'weight' => 2.0,
                'applies_to' => 'all',
                'is_active' => true,
                'order' => 21,
            ],

            // CRITÈRES SPÉCIFIQUES SOIGNANTS
            [
                'category' => 'performance',
                'code' => 'relation_patients',
                'name' => 'Relation avec les Patients',
                'description' => 'Qualité de la prise en charge des patients',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'soignant',
                'is_active' => true,
                'order' => 30,
            ],
            [
                'category' => 'performance',
                'code' => 'hygiene_securite',
                'name' => 'Hygiène et Sécurité',
                'description' => 'Respect des protocoles d\'hygiène et sécurité',
                'max_score' => 20,
                'weight' => 1.5,
                'applies_to' => 'soignant',
                'is_active' => true,
                'order' => 31,
            ],
        ];

        foreach ($criteria as $criterion) {
            EvaluationCriterion::updateOrCreate(
                ['code' => $criterion['code']],
                $criterion
            );
            $this->command->line("  ✓ {$criterion['name']}");
        }
    }

    protected function seedDeductionTypes()
    {
        $this->command->info('💰 Création des types de retenues...');

        $deductions = [
            [
                'code' => 'cnps',
                'name' => 'CNPS (Caisse Nationale de Prévoyance Sociale)',
                'description' => 'Cotisation salariale CNPS',
                'calculation_type' => 'percentage',
                'rate' => 4.20,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'code' => 'irpp',
                'name' => 'IRPP (Impôt sur le Revenu des Personnes Physiques)',
                'description' => 'Impôt sur le revenu - Barème progressif',
                'calculation_type' => 'progressive',
                'progressive_brackets' => [
                    ['min' => 0, 'max' => 2000000, 'rate' => 10],
                    ['min' => 2000001, 'max' => 3000000, 'rate' => 15],
                    ['min' => 3000001, 'max' => 5000000, 'rate' => 25],
                    ['min' => 5000001, 'max' => PHP_INT_MAX, 'rate' => 35],
                ],
                'is_active' => true,
                'order' => 2,
            ],
            [
                'code' => 'crtv',
                'name' => 'Redevance Audiovisuelle (CRTV)',
                'description' => 'Contribution CRTV',
                'calculation_type' => 'percentage',
                'rate' => 1.00,
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($deductions as $deduction) {
            QuotpartDeductionType::updateOrCreate(
                ['code' => $deduction['code']],
                $deduction
            );
            $this->command->line("  ✓ {$deduction['name']}");
        }

        $this->command->info('✅ Système de quote-parts initialisé avec succès !');
    }
}
