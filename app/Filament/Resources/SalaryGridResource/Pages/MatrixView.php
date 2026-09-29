<?php

namespace App\Filament\Resources\SalaryGridResource\Pages;

use App\Filament\Resources\SalaryGridResource;
use App\Models\SalaryGrid;
use App\Support\CameroonCivilServiceGrid;
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
        // ✅ GRILLES CAMEROUNAISES (D, C, B1, B2, A1, A2)
        $cameroonGrids = SalaryGrid::where('classification_type', 'cameroon')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('echelon')
            ->get()
            ->groupBy('category');

        // ✅ GRILLES NUMÉRIQUES (1-12)
        $numericGrids = SalaryGrid::where('classification_type', 'numeric')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('echelon')
            ->get()
            ->groupBy('category');

        return [
            'cameroonGrids' => $cameroonGrids,
            'numericGrids' => $numericGrids,
            'cameroonCategories' => array_keys(CameroonCivilServiceGrid::categoryOptions()),
            // Union de tous les échelons (H.ECH n'existe que pour A2 — sa colonne
            // affichera "-" pour les autres classes, ce qui est normal).
            'cameroonEchelons' => array_keys(CameroonCivilServiceGrid::allEchelonOptions()),
            'numericCategories' => range(1, 12),
            'numericEchelons' => range(1, 12),
        ];
    }
}
