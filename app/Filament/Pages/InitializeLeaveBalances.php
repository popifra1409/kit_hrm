<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;

class InitializeLeaveBalances extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-pointing-out';
    protected static ?string $navigationLabel = 'Initialiser Soldes';
    protected static ?string $title = 'Initialiser les Soldes de Congés';
    protected static ?string $navigationGroup = '🏖️ Gestion des Congés';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.initialize-leave-balances';

    public $year;
    public $leave_type_id;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('year')
                ->label('Année')
                ->options([
                    now()->year - 1 => now()->year - 1,
                    now()->year => now()->year,
                    now()->year + 1 => now()->year + 1,
                ])
                ->default(now()->year)
                ->required(),

            Select::make('leave_type_id')
                ->label('Type de congé')
                ->options(LeaveType::where('is_active', true)->pluck('name', 'id'))
                ->default(function () {
                    return LeaveType::where('code', 'CA')->first()?->id;
                })
                ->required(),
        ];
    }

    public function initializeAll()
    {
        $this->validate();

        try {
            $employees = Employee::where('is_active', true)->get();
            $leaveType = LeaveType::find($this->leave_type_id);

            if (!$leaveType) {
                throw new \Exception('Type de congé introuvable');
            }

            $created = 0;
            $updated = 0;

            foreach ($employees as $employee) {
                // Calculer le nombre de jours auquel l'employé a droit
                $entitledDays = $this->calculateEntitledDays($employee, $leaveType);

                $balance = LeaveBalance::firstOrNew([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $this->leave_type_id,
                    'year' => $this->year,
                ]);

                if ($balance->exists) {
                    $balance->total_entitled = $entitledDays;
                    $balance->recalculate();
                    $updated++;
                } else {
                    $balance->total_entitled = $entitledDays;
                    $balance->used = 0;
                    $balance->pending = 0;
                    $balance->available = $entitledDays;
                    $balance->save();
                    $created++;
                }
            }

            Notification::make()
                ->title('Initialisation réussie !')
                ->success()
                ->body("{$created} soldes créés, {$updated} soldes mis à jour pour {$leaveType->name} - {$this->year}")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur: ' . $e->getMessage())
                ->send();
        }
    }

    protected function calculateEntitledDays($employee, $leaveType)
    {
        // Pour le congé annuel, calculer selon l'ancienneté
        if ($leaveType->code === 'CA') {
            // Règle : 30 jours de base
            $baseDays = 30;

            // Bonus ancienneté : +1 jour tous les 5 ans (max +3 jours)
            if ($employee->recruitment_date) {
                $anciennete = $employee->recruitment_date->diffInYears(now());
                $bonusDays = min(floor($anciennete / 5), 3);
                return $baseDays + $bonusDays;
            }

            return $baseDays;
        }

        // Pour les autres types, utiliser la valeur par défaut
        return $leaveType->default_days;
    }

    public function getStats()
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        $withBalances = LeaveBalance::where('year', $this->year ?? now()->year)
            ->distinct('employee_id')
            ->count('employee_id');
        $withoutBalances = $totalEmployees - $withBalances;

        return [
            'total' => $totalEmployees,
            'with_balances' => $withBalances,
            'without_balances' => $withoutBalances,
        ];
    }
}
