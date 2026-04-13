<?php

namespace App\Helpers;

class NavigationHelper
{
    public static function getGroupMapping(): array
    {
        return [
            // Structure Organisationnelle
            'departments' => '🏢 Structure Organisationnelle',
            'services' => '🏢 Structure Organisationnelle',
            'positions' => '🏢 Structure Organisationnelle',

            // Gestion du Personnel
            'employees' => '👥 Gestion du Personnel',
            'employee-affectations' => '👥 Gestion du Personnel',
            'employee-assignment-histories' => '👥 Gestion du Personnel',
            'employee-advancement-histories' => '👥 Gestion du Personnel',

            // Congés & Absences
            'leave-types' => '📅 Congés & Absences',
            'leaves' => '📅 Congés & Absences',
            'absences' => '📅 Congés & Absences',

            // Contrats & Affectations
            'contracts' => '📋 Contrats & Affectations',
            'contract-types' => '📋 Contrats & Affectations',

            // Gestion Documentaire
            'document-categories' => '📚 Gestion Documentaire',
            'documents' => '📚 Gestion Documentaire',
            'cnps-pre-registrations' => '📚 Gestion Documentaire',

            // Temps & Présence
            'attendances' => '⏰ Temps & Présence',
            'replacements' => '⏰ Temps & Présence',

            // Développement RH
            'performance-evaluations' => '📈 Développement RH',
            'trainings' => '📈 Développement RH',

            // Gestion de la Paie
            'salary-grids' => '💰 Gestion de la Paie',
            'payrolls' => '💰 Gestion de la Paie',
            'payslips' => '💰 Gestion de la Paie',

            // Marchés Publics
            'suppliers' => '🏗️ Marchés Publics',
            'procurement-types' => '🏗️ Marchés Publics',
            'procurement-processes' => '🏗️ Marchés Publics',
            'bids' => '🏗️ Marchés Publics',
            'contracts-procurement' => '🏗️ Marchés Publics',

            // Paramétrages
            'hospital-settings' => '⚙️ Paramétrages',
            'notification-templates' => '⚙️ Paramétrages',
            'signatories' => '⚙️ Paramétrages',

            // Administration
            'users' => '🔧 Administration',
            'roles' => '🔧 Administration',
        ];
    }
}
