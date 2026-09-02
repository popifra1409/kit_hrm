<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Employee;

class RetirementAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Alertes Retraites';
    protected static ?string $title = 'Alertes et Suivi des Retraites';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.retirement-alerts';

    public $period = 'all';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRetirementQuery())
            ->columns([
                TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('qualification.name')
                    ->label('Qualification')
                    ->wrap(),

                TextColumn::make('currentService.name')
                    ->label('Service')
                    ->badge(),

                TextColumn::make('age')
                    ->label('Âge actuel')
                    ->getStateUsing(
                        fn($record) =>
                        $record->birth_date ? $record->birth_date->age : 'N/A'
                    )
                    ->suffix(' ans')
                    ->sortable(),

                TextColumn::make('retirement_date')
                    ->label('Date de retraite')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => $this->getRetirementColor($record)),

                TextColumn::make('time_remaining')
                    ->label('Temps restant')
                    ->getStateUsing(fn($record) => $this->getTimeRemaining($record))
                    ->badge()
                    ->color(fn($record) => $this->getRetirementColor($record)),

                BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->getStateUsing(fn($record) => $this->getPriority($record))
                    ->colors([
                        'danger' => 'Très Urgent',
                        'warning' => 'Urgent',
                        'info' => 'À planifier',
                        'secondary' => 'Normal',
                    ]),
            ])
            ->defaultSort('retirement_date', 'asc')
            ->filters([
                SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'very_urgent' => 'Très Urgent (< 3 mois)',
                        'urgent' => 'Urgent (3-6 mois)',
                        'plan' => 'À planifier (6-12 mois)',
                        'normal' => 'Normal (> 12 mois)',
                    ])
                    ->query(function ($query, $state) {
                        if (!$state['value']) return;

                        $now = now();
                        switch ($state['value']) {
                            case 'very_urgent':
                                $query->whereBetween('retirement_date', [$now, $now->copy()->addMonths(3)]);
                                break;
                            case 'urgent':
                                $query->whereBetween('retirement_date', [$now->copy()->addMonths(3), $now->copy()->addMonths(6)]);
                                break;
                            case 'plan':
                                $query->whereBetween('retirement_date', [$now->copy()->addMonths(6), $now->copy()->addYear()]);
                                break;
                            case 'normal':
                                $query->where('retirement_date', '>', $now->copy()->addYear());
                                break;
                        }
                    }),

                SelectFilter::make('service')
                    ->label('Service')
                    ->relationship('currentService', 'name'),
            ]);
    }

    protected function getRetirementQuery()
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereNotNull('retirement_date')
            ->where('retirement_date', '>', now());
    }

    protected function getTimeRemaining($record)
    {
        if (!$record->retirement_date) return 'Non défini';

        $now = now();
        $diff = $now->diffInMonths($record->retirement_date);

        if ($diff < 1) {
            $days = $now->diffInDays($record->retirement_date);
            return $days . ' jour(s)';
        } elseif ($diff < 12) {
            return $diff . ' mois';
        } else {
            $years = floor($diff / 12);
            $months = $diff % 12;
            return $years . ' an(s)' . ($months > 0 ? ' et ' . $months . ' mois' : '');
        }
    }

    protected function getRetirementColor($record)
    {
        if (!$record->retirement_date) return 'secondary';

        $months = now()->diffInMonths($record->retirement_date);

        if ($months < 3) return 'danger';
        if ($months < 6) return 'warning';
        if ($months < 12) return 'info';
        return 'success';
    }

    protected function getPriority($record)
    {
        if (!$record->retirement_date) return 'Normal';

        $months = now()->diffInMonths($record->retirement_date);

        if ($months < 3) return 'Très Urgent';
        if ($months < 6) return 'Urgent';
        if ($months < 12) return 'À planifier';
        return 'Normal';
    }

    public function getStats()
    {
        $veryUrgent = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now(), now()->addMonths(3)])
            ->count();

        $urgent = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now()->addMonths(3), now()->addMonths(6)])
            ->count();

        $toPlan = Employee::where('is_active', true)
            ->whereNotNull('retirement_date')
            ->whereBetween('retirement_date', [now()->addMonths(6), now()->addYear()])
            ->count();

        return [
            'very_urgent' => $veryUrgent,
            'urgent' => $urgent,
            'to_plan' => $toPlan,
            'total' => $veryUrgent + $urgent + $toPlan,
        ];
    }
}
