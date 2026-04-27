<?php

namespace App\Filament\Resources\SalaryGridResource\Pages;

use App\Filament\Resources\SalaryGridResource;
use App\Models\SalaryGrid;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class MatrixView extends Page
{
    protected static string $resource = SalaryGridResource::class;

    protected static string $view = 'filament.resources.salary-grid.matrix-view';

    protected static ?string $title = 'Vue Matricielle - Grille Salariale';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_list')
                ->label('Retour à la Liste')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn(): string => static::$resource::getUrl('index')),

            Actions\Action::make('create')
                ->label('Créer une Grille')
                ->icon('heroicon-o-plus')
                ->url(fn(): string => static::$resource::getUrl('create')),
        ];
    }

    public function getViewData(): array
    {
        $grids = SalaryGrid::where('is_active', true)
            ->orderBy('category')
            ->orderBy('echelon')
            ->get()
            ->groupBy('category');

        return [
            'grids' => $grids,
            'categories' => range(1, 12),
            'echelons' => range(1, 12),
        ];
    }
}
