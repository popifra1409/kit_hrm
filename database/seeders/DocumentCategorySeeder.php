<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentCategory;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Statuts et Règlements',
                'slug' => 'statuts-reglements',
                'description' => 'Statuts de l\'établissement, règlements intérieurs',
                'icon' => 'heroicon-o-scale',
                'color' => 'danger',
                'order' => 1,
            ],
            [
                'name' => 'Politiques et Procédures',
                'slug' => 'politiques-procedures',
                'description' => 'Politiques RH, procédures opérationnelles',
                'icon' => 'heroicon-o-document-text',
                'color' => 'primary',
                'order' => 2,
            ],
            [
                'name' => 'Notes de Service',
                'slug' => 'notes-service',
                'description' => 'Notes de service et circulaires',
                'icon' => 'heroicon-o-megaphone',
                'color' => 'warning',
                'order' => 3,
            ],
            [
                'name' => 'Communiqués',
                'slug' => 'communiques',
                'description' => 'Communiqués officiels et annonces',
                'icon' => 'heroicon-o-bell',
                'color' => 'info',
                'order' => 4,
            ],
            [
                'name' => 'Modèles et Formulaires',
                'slug' => 'modeles-formulaires',
                'description' => 'Modèles de contrats, formulaires administratifs',
                'icon' => 'heroicon-o-document-duplicate',
                'color' => 'success',
                'order' => 5,
            ],
            [
                'name' => 'Rapports',
                'slug' => 'rapports',
                'description' => 'Rapports d\'activité, bilans',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'secondary',
                'order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            DocumentCategory::create($category);
        }
    }
}
