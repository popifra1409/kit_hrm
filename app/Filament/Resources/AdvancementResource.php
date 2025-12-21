<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvancementResource\Pages;
use App\Filament\Resources\AdvancementResource\RelationManagers;
use App\Models\Advancement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvancementResource extends Resource
{
    protected static ?string $model = Advancement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations sur l\'Avancement')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::where('is_active', true)
                                    ->get()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = \App\Models\Employee::find($state);
                                    if ($employee) {
                                        $set('previous_category', $employee->category_current);
                                        $set('previous_echelon', $employee->echelon_number);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('type')
                            ->label('Type d\'avancement')
                            ->options([
                                'automatic' => 'Automatique',
                                'exceptional' => 'Exceptionnel',
                                'manual' => 'Manuel',
                            ])
                            ->default('automatic')
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('advancement_date')
                            ->label('Date d\'avancement')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Catégorie et Échelon')
                    ->schema([
                        Forms\Components\TextInput::make('previous_category')
                            ->label('Catégorie précédente')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('new_category')
                            ->label('Nouvelle catégorie')
                            ->required()
                            ->placeholder('Ex: 7/10'),

                        Forms\Components\TextInput::make('previous_echelon')
                            ->label('Échelon précédent')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('new_echelon')
                            ->label('Nouvel échelon')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(15),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de l\'avancement')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('decision_number')
                            ->label('N° de décision')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('previous_category')
                    ->label('Catégorie précédente')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('new_category')
                    ->label('Nouvelle catégorie')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('previous_echelon')
                    ->label('Échelon préc.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('new_echelon')
                    ->label('Nouvel échelon')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'automatic',
                        'warning' => 'exceptional',
                        'secondary' => 'manual',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'automatic' => 'Automatique',
                        'exceptional' => 'Exceptionnel',
                        'manual' => 'Manuel',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('advancement_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'automatic' => 'Automatique',
                        'exceptional' => 'Exceptionnel',
                        'manual' => 'Manuel',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
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
            'index' => Pages\ListAdvancements::route('/'),
            'create' => Pages\CreateAdvancement::route('/create'),
            'edit' => Pages\EditAdvancement::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Avancement';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Avancements';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-trending-up';
    }

    public static function getNavigationLabel(): string
    {
        return 'Avancements';
    }
}
