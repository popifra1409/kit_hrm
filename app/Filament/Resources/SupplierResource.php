<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getModelLabel(): string
    {
        return 'Fournisseur';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Fournisseurs';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏗️ Marchés Publics';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Raison Sociale')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('supplier_type')
                            ->label('Type de Fournisseur')
                            ->options([
                                'individual' => 'Personne Physique',
                                'company' => 'Société',
                                'consortium' => 'Consortium',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'works' => 'Travaux',
                                'goods' => 'Fournitures',
                                'services' => 'Services',
                                'consulting' => 'Conseils/Études',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'active' => 'Actif',
                                'suspended' => 'Suspendu',
                                'blacklisted' => 'Liste Noire',
                            ])
                            ->required()
                            ->default('active')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Identification Légale')
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->label('N° Registre de Commerce')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tax_number')
                            ->label('N° Contribuable')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('armp_number')
                            ->label('N° Agrément ARMP')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Coordonnées')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(2)
                            ->maxLength(65535),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->label('Pays')
                            ->default('Cameroun')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Personne de Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Nom du Contact')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Téléphone Contact')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Autres Informations')
                    ->schema([
                        Forms\Components\TagsInput::make('specialties')
                            ->label('Spécialités')
                            ->placeholder('Ajoutez les spécialités')
                            ->helperText('Appuyez sur Entrée après chaque spécialité'),

                        Forms\Components\TextInput::make('performance_score')
                            ->label('Score de Performance')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/100')
                            ->helperText('Note basée sur les prestations antérieures'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(65535),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Raison Sociale')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration_number')
                    ->label('N° RC')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->colors([
                        'primary' => 'works',
                        'success' => 'goods',
                        'info' => 'services',
                        'warning' => 'consulting',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'works' => 'Travaux',
                        'goods' => 'Fournitures',
                        'services' => 'Services',
                        'consulting' => 'Conseils',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('performance_score')
                    ->label('Score')
                    ->suffix('/100')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'blacklisted',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Actif',
                        'suspended' => 'Suspendu',
                        'blacklisted' => 'Liste Noire',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'works' => 'Travaux',
                        'goods' => 'Fournitures',
                        'services' => 'Services',
                        'consulting' => 'Conseils',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'suspended' => 'Suspendu',
                        'blacklisted' => 'Liste Noire',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
