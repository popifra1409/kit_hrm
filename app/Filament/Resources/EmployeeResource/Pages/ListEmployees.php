<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvel Employé')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->icon('heroicon-o-users')
                ->badge(fn() => \App\Models\Employee::count()),

            'active' => Tab::make('Actifs')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', true))
                ->badge(fn() => \App\Models\Employee::where('is_active', true)->count())
                ->badgeColor('success'),

            'medical' => Tab::make('Branche Médicale')
                ->icon('heroicon-o-heart')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where(function ($q) {
                        $q->whereHas('currentService', fn($sq) => $sq->where('type', 'medical'))
                            ->orWhereNotNull('department_id');
                    })
                )
                ->badge(fn() => \App\Models\Employee::whereHas(
                    'currentService',
                    fn($q) =>
                    $q->where('type', 'medical')
                )->orWhereNotNull('department_id')->count())
                ->badgeColor('success'),

            'administrative' => Tab::make('Branche Administrative')
                ->icon('heroicon-o-building-office')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereHas(
                        'currentService',
                        fn($sq) =>
                        $sq->whereIn('type', ['administrative', 'support', 'technical'])
                    )
                )
                ->badge(fn() => \App\Models\Employee::whereHas(
                    'currentService',
                    fn($q) =>
                    $q->whereIn('type', ['administrative', 'support', 'technical'])
                )->count())
                ->badgeColor('primary'),

            'managers' => Tab::make('Cadres de Management')
                ->icon('heroicon-o-user-group')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereHas('position', fn($q) => $q->where('is_managerial', true))
                )
                ->badge(fn() => \App\Models\Employee::whereHas(
                    'position',
                    fn($q) =>
                    $q->where('is_managerial', true)
                )->count())
                ->badgeColor('warning'),

            'inactive' => Tab::make('Inactifs')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_active', false))
                ->badge(fn() => \App\Models\Employee::where('is_active', false)->count())
                ->badgeColor('danger'),
        ];
    }
}
