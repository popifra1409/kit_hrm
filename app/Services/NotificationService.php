<?php

namespace App\Services;

use App\Models\UserNotification;
use App\Models\NotificationTemplate;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur
     */
    public function send(User $user, string $templateCode, array $variables = [], ?string $actionUrl = null, ?string $actionLabel = null)
    {
        $template = NotificationTemplate::where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            \Log::warning("Template de notification introuvable: {$templateCode}");
            return false;
        }

        $content = $template->generateContent($variables);

        // Notification système (interne)
        if ($template->system_enabled && SystemSetting::get('system_notifications_enabled', true)) {
            $this->sendSystemNotification($user, $template, $content['system'], $actionUrl, $actionLabel, $variables);
        }

        // Email
        if ($template->email_enabled && SystemSetting::get('email_enabled', false)) {
            $this->sendEmail($user, $content['email']);
        }

        // SMS
        if ($template->sms_enabled && SystemSetting::get('sms_enabled', false)) {
            $this->sendSMS($user, $content['sms']);
        }

        // WhatsApp
        if ($template->whatsapp_enabled && SystemSetting::get('whatsapp_enabled', false)) {
            $this->sendWhatsApp($user, $content['whatsapp']);
        }

        return true;
    }

    /**
     * Envoyer une notification système interne
     */
    protected function sendSystemNotification(User $user, NotificationTemplate $template, array $content, ?string $actionUrl, ?string $actionLabel, array $variables)
    {
        UserNotification::create([
            'user_id' => $user->id,
            'type' => $template->code,
            'title' => $content['title'],
            'message' => $content['body'],
            'icon' => $template->system_icon,
            'color' => $template->system_color,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'data' => $variables,
        ]);
    }

    /**
     * Envoyer un email
     */
    protected function sendEmail(User $user, array $content)
    {
        try {
            // TODO: Implémenter l'envoi d'email via Mail::send()
            // Pour l'instant, on log juste
            \Log::info("Email envoyé à {$user->email}: {$content['subject']}");
        } catch (\Exception $e) {
            \Log::error("Erreur envoi email: " . $e->getMessage());
        }
    }

    /**
     * Envoyer un SMS
     */
    protected function sendSMS(User $user, array $content)
    {
        try {
            // TODO: Implémenter l'envoi SMS selon le provider configuré
            \Log::info("SMS envoyé à {$user->employee?->phone}: {$content['body']}");
        } catch (\Exception $e) {
            \Log::error("Erreur envoi SMS: " . $e->getMessage());
        }
    }

    /**
     * Envoyer un message WhatsApp
     */
    protected function sendWhatsApp(User $user, array $content)
    {
        try {
            // TODO: Implémenter l'envoi WhatsApp selon le provider configuré
            \Log::info("WhatsApp envoyé à {$user->employee?->phone}: {$content['body']}");
        } catch (\Exception $e) {
            \Log::error("Erreur envoi WhatsApp: " . $e->getMessage());
        }
    }

    /**
     * Récupérer les notifications non lues d'un utilisateur
     */
    public function getUnreadNotifications(User $user)
    {
        return UserNotification::unreadForUser($user->id);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId)
    {
        $notification = UserNotification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(User $user)
    {
        UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
