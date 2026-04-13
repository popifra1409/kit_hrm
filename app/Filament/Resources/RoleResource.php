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

    public static function getModelLabel(): string
    {
        return 'Rôle';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rôles et Permissions';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🔧 Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
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
                            ->helperText('Identifiant unique du rôle (sans espaces)'),

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
                    ->schema([
                        Forms\Components\Tabs::make('permissions_by_module')
                            ->tabs([
                                // Utilisateurs
                                Forms\Components\Tabs\Tab::make('Utilisateurs')
                                    ->icon('heroicon-o-users')
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

                                // Congés & Absences
                                Forms\Components\Tabs\Tab::make('Congés & Absences')
                                    ->icon('heroicon-o-calendar')
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

                                // Paie
                                Forms\Components\Tabs\Tab::make('Paie')
                                    ->icon('heroicon-o-banknotes')
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
                                            ->gridDirection('row'),
                                    ]),

                                // Documents
                                Forms\Components\Tabs\Tab::make('Documents')
                                    ->icon('heroicon-o-document-text')
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

                                // Évaluations
                                Forms\Components\Tabs\Tab::make('Évaluations')
                                    ->icon('heroicon-o-star')
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

                                // Marchés Publics
                                Forms\Components\Tabs\Tab::make('Marchés Publics')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissions')
                                            ->label('')
                                            ->relationship('permissions', 'name')
                                            ->options(function () {
                                                return Permission::where('module', 'procurement')
                                                    ->pluck('description', 'id')
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),

                                // Contrats
                                Forms\Components\Tabs\Tab::make('Contrats')
                                    ->icon('heroicon-o-document-duplicate')
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

                                // Structure
                                Forms\Components\Tabs\Tab::make('Structure Organisationnelle')
                                    ->icon('heroicon-o-building-office-2')
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
                                            ->gridDirection('row'),
                                    ]),

                                // Rapports
                                Forms\Components\Tabs\Tab::make('Rapports')
                                    ->icon('heroicon-o-chart-bar')
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

                                // Paramètres
                                Forms\Components\Tabs\Tab::make('Paramètres')
                                    ->icon('heroicon-o-cog-6-tooth')
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
                            ->columnSpanFull(),
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
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'admin' => 'danger',
                        'drh' => 'success',
                        'daf' => 'warning',
                        'dg' => 'info',
                        'chef_service' => 'primary',
                        'employee' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->description)
                    ->searchable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->getStateUsing(function ($record) {
                        return \App\Models\User::role($record->name)->count();
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_permissions')
                    ->label('Permissions')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Permissions du rôle : ' . strtoupper($record->name))
                    ->modalContent(fn($record) => view('filament.modals.role-permissions', ['role' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),

                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->before(function ($record) {
                        // Ne pas supprimer si des utilisateurs ont ce rôle
                        $usersCount = \App\Models\User::role($record->name)->count();
                        if ($usersCount > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Impossible de supprimer')
                                ->danger()
                                ->body("Ce rôle est attribué à {$usersCount} utilisateur(s).")
                                ->persistent()
                                ->send();

                            return false;
                        }
                    }),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
