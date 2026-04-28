<?php

namespace App\Filament\Resources\DependentResource\Pages;

use App\Filament\Resources\DependentResource;
use App\Models\Employee;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class DependentsTree extends Page
{
    protected static string $resource = DependentResource::class;

    protected static string $view = 'filament.resources.dependent-resource.dependents-tree';

    protected static ?string $title = 'Ayants Droit - Vue Arborescente';

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    public function getEmployeesWithDependents()
    {
        return Employee::withCount('dependents')
            ->with(['dependents' => function ($query) {
                $query->orderBy('relationship')
                    ->orderBy('birth_date');
            }])
            ->whereHas('dependents') // ✅ Utiliser whereHas au lieu de having
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_list')
                ->label('Retour à la Liste')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => static::$resource::getUrl('index')),

            Actions\Action::make('create')
                ->label('Ajouter un Ayant Droit')
                ->icon('heroicon-o-plus')
                ->url(fn(): string => static::$resource::getUrl('create')),
        ];
    }
}
