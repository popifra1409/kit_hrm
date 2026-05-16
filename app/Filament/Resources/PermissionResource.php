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

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $navigationGroup = '🔧 Administration';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Permission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Permissions';
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
                            ->placeholder('Ex: view_employees, create_contracts')
                            ->helperText('Format: action_module (ex: view_employees, create_leaves)')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('module')
                            ->label('Module')
                            ->options([
                                'system' => '⚙️ Système',
                                'users' => '👤 Utilisateurs',
                                'employees' => '👥 Gestion du Personnel',
                                'health' => '🏥 Assurance Santé & Ayants Droit',
                                'contracts' => '📄 Contrats',
                                'leaves' => '🏖️ Congés & Absences',
                                'payroll' => '💰 Paie & Rémunération',
                                'evaluations' => '⭐ Évaluations',
                                'documents' => '📂 Documents',
                                'reports' => '📊 Rapports',
                                'settings' => '⚙️ Paramètres',
                                'structure' => '🏢 Structure Organisationnelle',
                            ])
                            ->searchable()
                            ->required()
                            ->helperText('Catégorie de la permission'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Décrivez ce que cette permission permet de faire')
                            ->columnSpanFull(),

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
                            ->gridDirection('row')
                            ->options([
                                'super_admin' => '🔴 Super Admin',
                                'admin' => '🔵 Admin',
                                'drh' => '👔 DRH',
                                'daf' => '💼 DAF',
                                'dg' => '🎯 DG',
                                'chef_service' => '👨‍💼 Chef de Service',
                                'employee' => '👤 Employé',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
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
                    ->copyMessage('Permission copiée')
                    ->weight('bold')
                    ->icon('heroicon-o-key'),

                Tables\Columns\BadgeColumn::make('module')
                    ->label('Module')
                    ->colors([
                        'danger' => 'system',
                        'info' => 'users',
                        'primary' => 'employees',
                        'success' => 'health',
                        'warning' => 'contracts',
                        'purple' => 'leaves',
                        'orange' => 'payroll',
                        'pink' => 'evaluations',
                        'cyan' => 'documents',
                        'indigo' => 'reports',
                        'gray' => 'settings',
                        'lime' => 'structure',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'system' => '⚙️ Système',
                        'users' => '👤 Utilisateurs',
                        'employees' => '👥 Personnel',
                        'health' => '🏥 Santé',
                        'contracts' => '📄 Contrats',
                        'leaves' => '🏖️ Congés',
                        'payroll' => '💰 Paie',
                        'evaluations' => '⭐ Évaluations',
                        'documents' => '📂 Documents',
                        'reports' => '📊 Rapports',
                        'settings' => '⚙️ Paramètres',
                        'structure' => '🏢 Structure',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(', ')
                    ->colors([
                        'danger' => 'super_admin',
                        'primary' => 'admin',
                        'success' => 'drh',
                        'warning' => 'daf',
                        'info' => 'dg',
                        'purple' => 'chef_service',
                        'gray' => 'employee',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'super_admin' => '🔴 Super Admin',
                        'admin' => '🔵 Admin',
                        'drh' => '👔 DRH',
                        'daf' => '💼 DAF',
                        'dg' => '🎯 DG',
                        'chef_service' => '👨‍💼 Chef Service',
                        'employee' => '👤 Employé',
                        default => $state,
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'system' => '⚙️ Système',
                        'users' => '👤 Utilisateurs',
                        'employees' => '👥 Gestion du Personnel',
                        'health' => '🏥 Assurance Santé & Ayants Droit',
                        'contracts' => '📄 Contrats',
                        'leaves' => '🏖️ Congés & Absences',
                        'payroll' => '💰 Paie & Rémunération',
                        'evaluations' => '⭐ Évaluations',
                        'documents' => '📂 Documents',
                        'reports' => '📊 Rapports',
                        'settings' => '⚙️ Paramètres',
                        'structure' => '🏢 Structure Organisationnelle',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->options([
                        'super_admin' => '🔴 Super Admin',
                        'admin' => '🔵 Admin',
                        'drh' => '👔 DRH',
                        'daf' => '💼 DAF',
                        'dg' => '🎯 DG',
                        'chef_service' => '👨‍💼 Chef de Service',
                        'employee' => '👤 Employé',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Voir')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Modifier')
                        ->icon('heroicon-o-pencil'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Supprimer')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélection')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('module', 'asc')
            ->groups([
                Tables\Grouping\Group::make('module')
                    ->label('Par Module')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    /**
     * Désactiver la navigation pour les non-admins
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super Admin et Admin uniquement
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
