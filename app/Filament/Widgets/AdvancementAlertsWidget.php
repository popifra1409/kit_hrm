<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\AdvancementCheckService;

class AdvancementAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.advancement-alerts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -10;

    public function getEligibleEmployees()
    {
        try {
            $service = new AdvancementCheckService();
            return $service->checkAllEligibleEmployees();
        } catch (\Exception $e) {
            \Log::error('Erreur widget alertes avancement: ' . $e->getMessage());
            return collect([]);
        }
    }

    public static function canView(): bool
    {
        // Visible pour tous pour le moment (ajustez selon vos besoins)
        return true;
    }
}
