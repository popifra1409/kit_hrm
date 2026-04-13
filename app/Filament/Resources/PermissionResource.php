<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    public static function getModelLabel(): string
    {
        return 'Permission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Permissions';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🔧 Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la Permission')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la Permission')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: view_employees, create_payrolls')
                            ->helperText('Format: action_module (ex: view_employees, create_leaves)'),

                        Forms\Components\Select::make('module')
                            ->label('Module')
                            ->options([
                                'employees' => 'Gestion du Personnel',
                                'leaves' => 'Congés & Absences',
                                'payroll' => 'Paie',
                                'documents' => 'Documents',
                                'settings' => 'Paramètres',
                                'reports' => 'Rapports',
                                'evaluations' => 'Évaluations',
                                'trainings' => 'Formations',
                                'procurement' => 'Marchés Publics',
                            ])
                            ->searchable()
                            ->helperText('Catégorie de la permission'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Décrivez ce que cette permission permet de faire'),

                        Forms\Components\Hidden::make('guard_name')
                            ->default('web'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rôles Associés')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label('Attribuer aux Rôles')
                            ->relationship('roles', 'name')
                            ->columns(3)
                            ->gridDirection('row'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Permission copiée'),

                Tables\Columns\BadgeColumn::make('module')
                    ->label('Module')
                    ->colors([
                        'primary' => 'employees',
                        'success' => 'leaves',
                        'warning' => 'payroll',
                        'info' => 'documents',
                        'danger' => 'settings',
                        'secondary' => fn($state) => in_array($state, ['reports', 'evaluations', 'trainings', 'procurement']),
                    ])
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->description)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(',')
                    ->colors([
                        'danger' => 'admin',
                        'success' => 'drh',
                        'warning' => 'daf',
                        'info' => 'dg',
                        'primary' => 'chef_service',
                        'secondary' => 'employee',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'employees' => 'Gestion du Personnel',
                        'leaves' => 'Congés & Absences',
                        'payroll' => 'Paie',
                        'documents' => 'Documents',
                        'settings' => 'Paramètres',
                        'reports' => 'Rapports',
                        'evaluations' => 'Évaluations',
                        'trainings' => 'Formations',
                        'procurement' => 'Marchés Publics',
                    ]),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
