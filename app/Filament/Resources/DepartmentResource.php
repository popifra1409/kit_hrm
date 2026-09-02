<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function getModelLabel(): string
    {
        return 'Département Médical';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Départements Médicaux';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏢 Structure Organisationnelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Départements Médicaux';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom du Département')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Département de Médecine Interne'),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('Ex: DMI'),

                                Forms\Components\TextInput::make('acronym')
                                    ->label('Acronyme')
                                    ->maxLength(50)
                                    ->placeholder('Ex: DMI'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                // NOUVEAU : Rattachement à une Direction
                                Forms\Components\Select::make('direction_id')
                                    ->label('Direction de Rattachement')
                                    ->relationship('direction', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->helperText('Direction administrative de tutelle')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom de la Direction')
                                            ->required(),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Code')
                                            ->required(),
                                    ]),

                                Forms\Components\Select::make('type')
                                    ->label('Type de Département')
                                    ->options([
                                        'medical' => 'Médical',
                                        'surgical' => 'Chirurgical',
                                        'diagnostic' => 'Diagnostic',
                                        'support' => 'Support',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('order')
                                    ->label('Ordre d\'Affichage')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Contact & Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('location')
                            ->label('Localisation')
                            ->maxLength(255)
                            ->placeholder('Pavillon X, Étage Y'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Ordre de tri dans les listes'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('direction.name')
                    ->label('Direction de Rattachement')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Département')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->type_label)
                    ->colors([
                        'info' => 'medical',
                        'danger' => 'surgical',
                        'warning' => 'diagnostic',
                        'success' => 'support',
                    ]),

                Tables\Columns\TextColumn::make('departmentHead.full_name')
                    ->label('Chef de Département')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_employee_count')
                    ->label('Employés')
                    ->getStateUsing(fn($record) => $record->total_employee_count)
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_bed_capacity')
                    ->label('Capacité Lits')
                    ->getStateUsing(fn($record) => $record->total_bed_capacity)
                    ->suffix(' lits')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'medical' => 'Médecine',
                        'surgical' => 'Chirurgie',
                        'diagnostic' => 'Diagnostic',
                        'support' => 'Support',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement'),

                Tables\Filters\Filter::make('has_head')
                    ->label('Avec Chef de Département')
                    ->query(fn($query) => $query->whereNotNull('department_head_id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
