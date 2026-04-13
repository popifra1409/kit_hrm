<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ClearCacheController extends Controller
{
    public function __invoke()
    {
        // Vérifier que l'utilisateur est admin ou drh
        if (!auth()->user()->hasAnyRole(['admin', 'drh'])) {
            Notification::make()
                ->title('Accès refusé')
                ->danger()
                ->body('Vous n\'avez pas les permissions nécessaires.')
                ->send();

            return redirect()->back();
        }

        try {
            // Vider tous les caches
            Artisan::call('optimize:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            // Cache des permissions (Spatie)
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                Artisan::call('permission:cache-reset');
            }

            Notification::make()
                ->title('✅ Cache vidé avec succès')
                ->success()
                ->body('Tous les caches ont été vidés.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Erreur')
                ->danger()
                ->body('Impossible de vider le cache : ' . $e->getMessage())
                ->send();
        }

        return redirect()->back();
    }
}
