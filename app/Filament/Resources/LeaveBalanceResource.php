<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Filament\Resources\LeaveBalanceResource\RelationManagers;
use App\Models\LeaveBalance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employé')
                    ->options(function () {
                        return \App\Models\Employee::where('is_active', true)
                            ->get()
                            ->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('leave_type_id')
                    ->label('Type de congé')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('year')
                    ->label('Année')
                    ->required()
                    ->numeric()
                    ->default(now()->year)
                    ->minValue(2020)
                    ->maxValue(2030),

                Forms\Components\TextInput::make('total_entitled')
                    ->label('Total auquel l\'employé a droit')
                    ->required()
                    ->numeric()
                    ->suffix('jours'),

                Forms\Components\TextInput::make('used')
                    ->label('Jours utilisés')
                    ->numeric()
                    ->default(0)
                    ->suffix('jours'),

                Forms\Components\TextInput::make('pending')
                    ->label('Jours en attente')
                    ->numeric()
                    ->default(0)
                    ->suffix('jours'),

                Forms\Components\TextInput::make('available')
                    ->label('Jours disponibles')
                    ->numeric()
                    ->disabled()
                    ->suffix('jours')
                    ->helperText('Calculé automatiquement : Total - Utilisés - En attente'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Type de congé')
                    ->badge(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Année')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_entitled')
                    ->label('Droit total')
                    ->suffix(' j')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('used')
                    ->label('Utilisés')
                    ->suffix(' j')
                    ->alignCenter()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('pending')
                    ->label('En attente')
                    ->suffix(' j')
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('available')
                    ->label('Disponibles')
                    ->suffix(' j')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Année')
                    ->options([
                        now()->year - 1 => now()->year - 1,
                        now()->year => now()->year,
                        now()->year + 1 => now()->year + 1,
                    ])
                    ->default(now()->year),

                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('Type de congé')
                    ->relationship('leaveType', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveBalances::route('/'),
            'create' => Pages\CreateLeaveBalance::route('/create'),
            'edit' => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Solde de Congé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Soldes de Congés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏖️ Congés & Absences';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Soldes de Congés';
    }
}
