<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Congés
            [
                'code' => 'leave_submitted',
                'name' => 'Demande de Congé Soumise',
                'category' => 'leave',
                'description' => 'Notification envoyée à l\'employé après soumission de sa demande',
                'email_enabled' => true,
                'system_enabled' => true,
                'email_subject' => 'Votre demande de congé a été soumise',
                'email_body' => 'Bonjour {{employee_name}},

Votre demande de congé de type {{leave_type}} du {{start_date}} au {{end_date}} a bien été soumise et est en attente d\'approbation.

Cordialement,
Service RH - CHUY',
                'system_title' => 'Demande de congé soumise',
                'system_body' => 'Votre demande de {{leave_type}} du {{start_date}} au {{end_date}} a été soumise.',
                'system_icon' => 'heroicon-o-clock',
                'system_color' => 'info',
                'available_variables' => ['employee_name', 'leave_type', 'start_date', 'end_date', 'days'],
            ],
            [
                'code' => 'leave_approved',
                'name' => 'Demande de Congé Approuvée',
                'category' => 'leave',
                'description' => 'Notification envoyée quand le congé est approuvé',
                'email_enabled' => true,
                'sms_enabled' => false,
                'system_enabled' => true,
                'email_subject' => '✅ Votre demande de congé a été approuvée',
                'email_body' => 'Bonjour {{employee_name}},

Nous avons le plaisir de vous informer que votre demande de {{leave_type}} du {{start_date}} au {{end_date}} ({{days}} jours) a été approuvée.

Bon repos !

Service RH - CHUY',
                'sms_body' => 'Congé approuvé: {{leave_type}} du {{start_date}} au {{end_date}}. Bon repos! - CHUY RH',
                'system_title' => 'Congé approuvé',
                'system_body' => 'Votre {{leave_type}} du {{start_date}} au {{end_date}} a été approuvé.',
                'system_icon' => 'heroicon-o-check-circle',
                'system_color' => 'success',
                'available_variables' => ['employee_name', 'leave_type', 'start_date', 'end_date', 'days', 'approved_by'],
            ],
            [
                'code' => 'leave_rejected',
                'name' => 'Demande de Congé Rejetée',
                'category' => 'leave',
                'description' => 'Notification envoyée quand le congé est rejeté',
                'email_enabled' => true,
                'system_enabled' => true,
                'email_subject' => 'Votre demande de congé a été rejetée',
                'email_body' => 'Bonjour {{employee_name}},

Nous sommes au regret de vous informer que votre demande de {{leave_type}} du {{start_date}} au {{end_date}} n\'a pas pu être approuvée.

Motif: {{rejection_reason}}

N\'hésitez pas à contacter le service RH pour plus d\'informations.

Service RH - CHUY',
                'system_title' => 'Congé rejeté',
                'system_body' => 'Votre demande de {{leave_type}} a été rejetée. Motif: {{rejection_reason}}',
                'system_icon' => 'heroicon-o-x-circle',
                'system_color' => 'danger',
                'available_variables' => ['employee_name', 'leave_type', 'start_date', 'end_date', 'rejection_reason'],
            ],

            // Paie
            [
                'code' => 'payroll_ready',
                'name' => 'Bulletin de Paie Disponible',
                'category' => 'payroll',
                'description' => 'Notification quand le bulletin de paie est prêt',
                'email_enabled' => true,
                'system_enabled' => true,
                'email_subject' => 'Votre bulletin de paie de {{month}} {{year}} est disponible',
                'email_body' => 'Bonjour {{employee_name}},

Votre bulletin de paie du mois de {{month}} {{year}} est maintenant disponible.

Montant net: {{net_salary}} FCFA

Vous pouvez le consulter et le télécharger depuis votre espace employé.

Service RH - CHUY',
                'system_title' => 'Bulletin de paie disponible',
                'system_body' => 'Votre bulletin de {{month}} {{year}} est prêt. Net: {{net_salary}} FCFA',
                'system_icon' => 'heroicon-o-document-text',
                'system_color' => 'success',
                'available_variables' => ['employee_name', 'month', 'year', 'net_salary', 'gross_salary'],
            ],

            // Avancements
            [
                'code' => 'advancement_due',
                'name' => 'Avancement Éligible',
                'category' => 'advancement',
                'description' => 'Alerte pour un employé éligible à l\'avancement',
                'email_enabled' => true,
                'system_enabled' => true,
                'email_subject' => 'Vous êtes éligible à un avancement',
                'email_body' => 'Bonjour {{employee_name}},

Nous avons le plaisir de vous informer que vous êtes éligible à un avancement d\'échelon.

Échelon actuel: {{current_echelon}}
Nouvel échelon: {{new_echelon}}
Date effective: {{effective_date}}

Une décision officielle vous sera transmise prochainement.

Félicitations !

Service RH - CHUY',
                'system_title' => 'Éligible à l\'avancement',
                'system_body' => 'Vous êtes éligible à l\'avancement: {{current_echelon}} → {{new_echelon}}',
                'system_icon' => 'heroicon-o-arrow-trending-up',
                'system_color' => 'success',
                'available_variables' => ['employee_name', 'current_echelon', 'new_echelon', 'effective_date'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::create($template);
        }

        $this->command->info('✅ Templates de notification créés avec succès!');
    }
}
