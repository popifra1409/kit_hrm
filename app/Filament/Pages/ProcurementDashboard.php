<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Procurement;
use App\Models\Bid;
use App\Models\ProcurementContract;
use App\Models\Supplier;
use App\Models\ProcurementType;

class ProcurementDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Tableau de Bord MP';
    protected static ?string $title = 'Tableau de Bord - Marchés Publics';
    protected static ?string $navigationGroup = '🏗️ Marchés Publics';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.procurement-dashboard';

    public function getStats()
    {
        $currentYear = now()->year;

        return [
            // Marchés
            'total_procurements' => Procurement::count(),
            'active_procurements' => Procurement::whereIn('status', ['published', 'bids_received', 'evaluation'])->count(),
            'draft_procurements' => Procurement::where('status', 'draft')->count(),
            'awarded_procurements' => Procurement::where('status', 'awarded')->count(),

            // Montants
            'total_estimated' => Procurement::sum('estimated_amount'),
            'total_awarded' => Procurement::sum('awarded_amount'),
            'total_contracts' => ProcurementContract::sum('total_amount'),

            // Offres
            'total_bids' => Bid::count(),
            'pending_bids' => Bid::whereIn('status', ['submitted', 'under_review'])->count(),
            'awarded_bids' => Bid::where('status', 'awarded')->count(),

            // Contrats
            'total_contracts_count' => ProcurementContract::count(),
            'active_contracts' => ProcurementContract::whereIn('status', ['signed', 'in_execution'])->count(),
            'completed_contracts' => ProcurementContract::where('status', 'completed')->count(),

            // Fournisseurs
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('status', 'active')->count(),
            'blacklisted_suppliers' => Supplier::where('status', 'blacklisted')->count(),

            // Année en cours
            'year_procurements' => Procurement::whereYear('created_at', $currentYear)->count(),
            'year_amount' => Procurement::whereYear('created_at', $currentYear)->sum('estimated_amount'),
        ];
    }

    public function getProcurementsByStatus()
    {
        return Procurement::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getProcurementsByType()
    {
        return Procurement::with('procurementType')
            ->get()
            ->groupBy('procurementType.name')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'estimated' => $group->sum('estimated_amount'),
                    'awarded' => $group->sum('awarded_amount'),
                ];
            });
    }

    public function getProcurementsByProcedure()
    {
        return Procurement::selectRaw('procedure, COUNT(*) as count, SUM(estimated_amount) as total')
            ->groupBy('procedure')
            ->get()
            ->map(function ($item) {
                return [
                    'procedure' => $this->formatProcedure($item->procedure),
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            });
    }

    public function getRecentProcurements()
    {
        return Procurement::with(['procurementType', 'awardedSupplier'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getUpcomingDeadlines()
    {
        return Procurement::where('deadline_submission', '>=', now())
            ->where('deadline_submission', '<=', now()->addDays(30))
            ->orderBy('deadline_submission')
            ->limit(10)
            ->get();
    }

    public function getTopSuppliers()
    {
        return Supplier::withCount('contracts')
            ->with('contracts')
            ->get()
            ->filter(fn($supplier) => $supplier->contracts_count > 0)
            ->sortByDesc('contracts_count')
            ->take(10)
            ->map(function ($supplier) {
                return [
                    'supplier' => $supplier,
                    'count' => $supplier->contracts_count,
                    'total_amount' => $supplier->contracts->sum('total_amount'),
                ];
            });
    }

    public function getMonthlyTrend()
    {
        $currentYear = now()->year;
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            $procurements = Procurement::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $i)
                ->get();

            $months[] = [
                'month' => $i,
                'month_name' => $this->getMonthNameFr($i),
                'count' => $procurements->count(),
                'estimated' => $procurements->sum('estimated_amount'),
                'awarded' => $procurements->sum('awarded_amount'),
            ];
        }

        return $months;
    }

    public function getContractExecutionStatus()
    {
        return ProcurementContract::with('executions')
            ->where('status', 'in_execution')
            ->get()
            ->map(function ($contract) {
                $latestExecution = $contract->executions()->latest('report_date')->first();
                return [
                    'contract' => $contract,
                    'progress' => $latestExecution?->progress_percentage ?? 0,
                    'is_delayed' => $latestExecution?->delay_days ?? 0 > 0,
                    'delay_days' => $latestExecution?->delay_days ?? 0,
                ];
            })
            ->sortByDesc('progress');
    }

    public function getARMPStatus()
    {
        return [
            'pending' => Procurement::where('armp_status', 'pending')->count(),
            'approved' => Procurement::where('armp_status', 'approved')->count(),
            'rejected' => Procurement::where('armp_status', 'rejected')->count(),
            'not_required' => Procurement::where('armp_status', 'not_required')->count(),
        ];
    }

    protected function formatProcedure($procedure)
    {
        return match ($procedure) {
            'open_tender' => 'AO Ouvert',
            'restricted_tender' => 'AO Restreint',
            'consultation' => 'Consultation',
            'direct_agreement' => 'Gré à Gré',
            'request_for_quote' => 'Demande Cotation',
            default => $procedure,
        };
    }

    protected function getMonthNameFr($month)
    {
        $months = [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc'
        ];

        return $months[$month] ?? '';
    }
}
