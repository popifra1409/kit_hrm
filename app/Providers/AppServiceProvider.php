<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Failed as FailedEvent;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Auth\Events\Logout as LogoutEvent;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Models\AccountActivationRequest;
use App\Models\AccountDeletion;
use App\Models\CensusSubmission;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeAffectation;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Observers\EmployeeObserver;
use App\Observers\EmployeeAffectationObserver;
use App\Observers\LeaveObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /// Enregistrer les observers
        Employee::observe(EmployeeObserver::class);
        EmployeeAffectation::observe(EmployeeAffectationObserver::class);
        Leave::observe(LeaveObserver::class);

        // Journal d'audit : uniquement les modèles à enjeu (comptes, carrière, paie,
        // recensement). Les listes simples (types de congé, grilles, paramètres) sont
        // déjà tracées par les actions du panneau, et les lectures ne sont pas consignées.
        foreach ([
            User::class,
            Employee::class,
            EmployeeAffectation::class,
            Contract::class,
            Leave::class,
            Payroll::class,
            CensusSubmission::class,
            AccountActivationRequest::class,
            AccountDeletion::class,
        ] as $auditedModel) {
            $auditedModel::observe(ActivityLogObserver::class);
        }

        // Journal d'audit des connexions au back-office. L'application web passe par
        // l'API (Hash::check + jeton Sanctum, consignée dans AuthController) : ces
        // événements ne concernent donc que le formulaire /admin, sans double ligne.
        Event::listen(function (LoginEvent $event) {
            if ($event->guard !== 'web') {
                return;
            }

            ActivityLogger::record(
                action: 'login_success',
                subject: $event->user,
                description: 'Connexion au back-office.',
                context: ActivityLog::CONTEXT_PANEL,
            );
        });

        Event::listen(function (FailedEvent $event) {
            if ($event->guard !== 'web') {
                return;
            }

            // L'adresse saisie est la seule trace d'une tentative qui ne cible aucun
            // compte connu ; le mot de passe, lui, n'est jamais transmis au logger.
            ActivityLogger::record(
                action: 'login_failed',
                status: ActivityLog::STATUS_FAILED,
                reason: $event->user ? 'Mot de passe incorrect.' : 'Adresse e-mail inconnue dans le système.',
                properties: ['adresse_saisie' => $event->credentials['email'] ?? null],
                actorMatricule: $event->user?->employee?->matricule,
                context: ActivityLog::CONTEXT_PANEL,
            );
        });

        Event::listen(function (LogoutEvent $event) {
            if ($event->guard !== 'web') {
                return;
            }

            ActivityLogger::record(
                action: 'logout',
                subject: $event->user,
                description: 'Déconnexion du back-office.',
                context: ActivityLog::CONTEXT_PANEL,
            );
        });

        // Autoriser l'accès à la doc API (Scramble) en dehors de l'environnement local
        // uniquement pour les super_admin/admin déjà connectés à l'app.
        Gate::define('viewApiDocs', function ($user = null) {
            return $user && $user->hasAnyRole(['super_admin', 'admin']);
        });
    }
}
