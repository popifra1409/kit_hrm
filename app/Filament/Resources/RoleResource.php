<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Rôles';

    protected static ?string $navigationGroup = '🔧 Administration';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Rôle';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rôles et Permissions';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du Rôle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du Rôle')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: admin, drh, chef_service')
                            ->helperText('Identifiant unique du rôle (sans espaces, en minuscules)')
                            ->rules(['alpha_dash'])
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Décrivez les responsabilités de ce rôle')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('guard_name')
                            ->default('web'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Permissions')
                    ->description('Sélectionnez les permissions à attribuer à ce rôle')
                    ->schema([
                        Forms\Components\Tabs::make('permissions_by_module')
                            ->tabs([
                                // Système
                                Forms\Components\Tabs\Tab::make('Système')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->badge(fn() => Permission::where('module', 'system')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'system')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->helperText('⚠️ Permissions critiques - Réservées au Super Admin'),
                                    ]),

                                // Utilisateurs
                                Forms\Components\Tabs\Tab::make('Utilisateurs')
                                    ->icon('heroicon-o-users')
                                    ->badge(fn() => Permission::where('module', 'users')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'users')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Employés
                                Forms\Components\Tabs\Tab::make('Employés')
                                    ->icon('heroicon-o-user-group')
                                    ->badge(fn() => Permission::where('module', 'employees')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'employees')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Santé & Ayants Droit
                                Forms\Components\Tabs\Tab::make('Santé & Ayants Droit')
                                    ->icon('heroicon-o-heart')
                                    ->badge(fn() => Permission::where('module', 'health')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'health')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Contrats
                                Forms\Components\Tabs\Tab::make('Contrats')
                                    ->icon('heroicon-o-document-duplicate')
                                    ->badge(fn() => Permission::where('module', 'contracts')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'contracts')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Congés & Absences
                                Forms\Components\Tabs\Tab::make('Congés & Absences')
                                    ->icon('heroicon-o-calendar')
                                    ->badge(fn() => Permission::where('module', 'leaves')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'leaves')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Paie & Rémunération
                                Forms\Components\Tabs\Tab::make('Paie & Rémunération')
                                    ->icon('heroicon-o-banknotes')
                                    ->badge(fn() => Permission::where('module', 'payroll')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'payroll')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->helperText('Grille salariale, Quote-parts, Activités médicales'),
                                    ]),

                                // Évaluations
                                Forms\Components\Tabs\Tab::make('Évaluations')
                                    ->icon('heroicon-o-star')
                                    ->badge(fn() => Permission::where('module', 'evaluations')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'evaluations')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Formations
                                Forms\Components\Tabs\Tab::make('Formations')
                                    ->icon('heroicon-o-academic-cap')
                                    ->badge(fn() => Permission::where('module', 'trainings')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'trainings')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Documents
                                Forms\Components\Tabs\Tab::make('Documents')
                                    ->icon('heroicon-o-document-text')
                                    ->badge(fn() => Permission::where('module', 'documents')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'documents')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Rapports
                                Forms\Components\Tabs\Tab::make('Rapports')
                                    ->icon('heroicon-o-chart-bar')
                                    ->badge(fn() => Permission::where('module', 'reports')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'reports')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Structure Organisationnelle
                                Forms\Components\Tabs\Tab::make('Structure Organisationnelle')
                                    ->icon('heroicon-o-building-office-2')
                                    ->badge(fn() => Permission::where('module', 'structure')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'structure')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->helperText('Départements, Services, Postes'),
                                    ]),

                                // Paramètres
                                Forms\Components\Tabs\Tab::make('Paramètres')
                                    ->icon('heroicon-o-cog')
                                    ->badge(fn() => Permission::where('module', 'settings')->count())
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'settings')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->activeTab(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rôle')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'super_admin' => '🔴 SUPER ADMIN',
                        'admin' => '🔵 ADMIN',
                        'drh' => '👔 DRH',
                        'daf' => '💼 DAF',
                        'dg' => '🎯 DG',
                        'chef_service' => '👨‍💼 CHEF DE SERVICE',
                        'employee' => '👤 EMPLOYÉ',
                        default => strtoupper($state),
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'primary',
                        'drh' => 'success',
                        'daf' => 'warning',
                        'dg' => 'info',
                        'chef_service' => 'purple',
                        'employee' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-key'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->getStateUsing(function ($record) {
                        return \App\Models\User::role($record->name)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-users'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_users')
                    ->label('Avec utilisateurs')
                    ->query(function ($query) {
                        $roleIds = \App\Models\User::with('roles')
                            ->get()
                            ->pluck('roles')
                            ->flatten()
                            ->pluck('id')
                            ->unique();

                        return $query->whereIn('id', $roleIds);
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_permissions')
                        ->label('Voir Permissions')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Permissions : ' . strtoupper($record->name))
                        ->modalDescription(fn($record) => $record->permissions->count() . ' permissions attribuées')
                        ->modalContent(fn($record) => view('filament.modals.role-permissions', ['role' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fermer'),

                    Tables\Actions\ViewAction::make()
                        ->label('Voir')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Modifier')
                        ->icon('heroicon-o-pencil'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Supprimer')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->before(function ($record, $action) {
                            // Bloquer suppression si super_admin
                            if ($record->name === 'super_admin') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Action interdite')
                                    ->danger()
                                    ->body('Le rôle Super Admin ne peut pas être supprimé.')
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                                return;
                            }

                            // Bloquer si des utilisateurs ont ce rôle
                            $usersCount = \App\Models\User::role($record->name)->count();
                            if ($usersCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Impossible de supprimer')
                                    ->danger()
                                    ->body("Ce rôle est attribué à {$usersCount} utilisateur(s). Réassignez-les d'abord.")
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélection')
                        ->requiresConfirmation()
                        ->before(function ($records, $action) {
                            // Bloquer si super_admin dans la sélection
                            if ($records->contains('name', 'super_admin')) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Action interdite')
                                    ->danger()
                                    ->body('Le rôle Super Admin ne peut pas être supprimé.')
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
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
