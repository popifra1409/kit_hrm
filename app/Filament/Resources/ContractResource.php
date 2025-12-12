<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du Contrat')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::all()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('contract_type_id')
                            ->label('Type de contrat')
                            ->relationship('contractType', 'name')
                            ->searchable()
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('contract_number')
                            ->label('Numéro de contrat')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Période et Salaire')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->nullable()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('base_salary')
                            ->label('Salaire de base')
                            ->numeric()
                            ->prefix('FCFA')
                            ->nullable(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Clauses et Statut')
                    ->schema([
                        Forms\Components\Textarea::make('terms')
                            ->label('Clauses du contrat')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'active' => 'Actif',
                                'expired' => 'Expiré',
                                'terminated' => 'Résilié',
                                'renewed' => 'Renouvelé',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_current')
                            ->label('Contrat actuel')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Résiliation')
                    ->schema([
                        Forms\Components\DatePicker::make('termination_date')
                            ->label('Date de résiliation')
                            ->nullable()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Textarea::make('termination_reason')
                            ->label('Motif de résiliation')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contract_number')
                    ->label('N° Contrat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'danger' => ['expired', 'terminated'],
                        'warning' => 'renewed',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'terminated' => 'Résilié',
                        'renewed' => 'Renouvelé',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Actuel')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'terminated' => 'Résilié',
                        'renewed' => 'Renouvelé',
                    ]),
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return '📋 Contrats & Affectations';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-check';
    }

    public static function getNavigationLabel(): string
    {
        return 'Contrats';
    }
    public static function getModelLabel(): string
    {
        return 'Contrat';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Contrats';
    }
}
