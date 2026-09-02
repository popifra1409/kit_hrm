<?php

namespace App\Filament\Pages;

use App\Models\TradeBody;
use App\Models\Employee;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class CorpsMetierStats extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.corps-metier-stats';
    protected static ?string $navigationGroup = '🏢 Structure Organisationnelle';
    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return 'Effectifs par Corps de Métier';
    }

    public function getTitle(): string
    {
        return 'Effectifs par Corps de Métier';
    }

    public bool $activeOnly = true;

    // vide = tous les corps de métier
    public ?array $tradeBodyIds = [];

    public function mount(): void
    {
        $this->form->fill([
            'tradeBodyIds' => [],
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tradeBodyIds')
                    ->label('Corps de métier à afficher')
                    ->multiple()
                    ->options(TradeBody::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les corps de métier')
                    ->live()
                    ->afterStateUpdated(fn($state) => $this->tradeBodyIds = $state ?? []),
            ]);
    }

    public function toggleActiveOnly(): void
    {
        $this->activeOnly = !$this->activeOnly;
    }

    protected function employeeConstraint(): \Closure
    {
        return function ($query) {
            if ($this->activeOnly) {
                $query->where('is_active', true);
            }
        };
    }

    public function getViewData(): array
    {
        $constraint = $this->employeeConstraint();

        $tradeBodiesQuery = TradeBody::query()
            ->withCount(['employees' => $constraint]);

        if (!empty($this->tradeBodyIds)) {
            $tradeBodiesQuery->whereIn('id', $this->tradeBodyIds);
        }

        $tradeBodies = $tradeBodiesQuery->orderByDesc('employees_count')->get();

        $baseEmployeeQuery = $this->activeOnly
            ? Employee::where('is_active', true)
            : Employee::query();

        $totalEmployees = (clone $baseEmployeeQuery)->count();

        // Répartition par statut administratif
        $statusLabels = [
            'fonctionnaire_affecte' => '🏛️ Fonctionnaire Affecté',
            'fonctionnaire_detache' => '🔄 Fonctionnaire en Détachement',
            'contractuel_fp' => '📋 Contractuel Fonction Publique',
            'contractuel_structure' => '🏥 Contractuel de la Structure',
            'stagiaire' => '🎓 Stagiaire',
        ];

        $statusCounts = (clone $baseEmployeeQuery)
            ->selectRaw('administrative_status, count(*) as total')
            ->groupBy('administrative_status')
            ->pluck('total', 'administrative_status');

        $byStatus = collect($statusLabels)->map(function ($label, $key) use ($statusCounts, $totalEmployees) {
            $count = $statusCounts[$key] ?? 0;
            return [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'pct' => $totalEmployees > 0 ? round(($count / $totalEmployees) * 100, 1) : 0,
            ];
        })->values();

        $undefinedStatus = ($statusCounts->get(null, 0)) + ($statusCounts->get('', 0));

        $maxCount = $tradeBodies->max('employees_count') ?: 1;

        $unassigned = (clone $baseEmployeeQuery)->whereNull('trade_body_id')->count();

        return [
            'tradeBodies' => $tradeBodies,
            'byStatus' => $byStatus,
            'undefinedStatus' => $undefinedStatus,
            'totalEmployees' => $totalEmployees,
            'maxCount' => $maxCount,
            'unassigned' => $unassigned,
            'activeOnly' => $this->activeOnly,
        ];
    }
}
