<?php

namespace App\Filament\Pages\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as Base;

/**
 * Page de connexion du back-office, identique à celle de Filament sauf sur un point :
 * le blocage temporaire après cinq échecs.
 */
class Login extends Base
{
    /**
     * Le compteur de Filament est levé avant toute vérification d'identifiants et ne
     * dispatche aucun événement : sans ce crochet, un verrouillage ne laisserait aucune
     * trace dans le journal, alors même que c'est l'événement le plus utile à y voir.
     * On laisse Filament envoyer sa notification, on consigne seulement avant.
     */
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        $adresseSaisie = $this->data['email'] ?? null;
        $compte = $adresseSaisie === null ? null : User::where('email', $adresseSaisie)->first();

        ActivityLogger::record(
            action: 'login_locked',
            status: ActivityLog::STATUS_BLOCKED,
            subject: $compte,
            reason: 'Le formulaire du back-office refuse toute nouvelle tentative : cinq échecs '
                . 'consécutifs depuis cette adresse.',
            properties: [
                'adresse_saisie' => $adresseSaisie,
                'secondes_avant_reprise' => $exception->secondsUntilAvailable,
            ],
            actorMatricule: $compte?->employee?->matricule,
            context: ActivityLog::CONTEXT_PANEL,
        );

        return parent::getRateLimitedNotification($exception);
    }
}
