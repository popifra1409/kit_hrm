<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Employee;

class RecentEmployeesTable extends BaseWidget
{
    protected static ?string $heading = 'Derniers Employés Recrutés';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('is_active', true)
                    ->latest('recruitment_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('qualification.name')
                    ->label('Qualification')
                    ->wrap(),

                Tables\Columns\TextColumn::make('personnel_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => 'soignant',
                        'warning' => 'non_soignant',
                    ])
                    ->formatStateUsing(
                        fn(string $state): string =>
                        $state === 'soignant' ? 'Soignant' : 'Non-Soignant'
                    ),

                Tables\Columns\TextColumn::make('recruitment_date')
                    ->label('Date recrutement')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currentService.name')
                    ->label('Service')
                    ->badge(),
            ]);
    }
}
