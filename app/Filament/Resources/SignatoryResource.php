<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SignatoryResource\Pages;
use App\Models\Signatory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SignatoryResource extends Resource
{
    protected static ?string $model = Signatory::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function getModelLabel(): string
    {
        return 'Signataire';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Signataires';
    }

    public static function getNavigationGroup(): ?string
    {
        return '⚙️ Paramétrage';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du Signataire')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur (optionnel)')
                            ->options(User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->reactive()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->employee) {
                                        $set('full_name', $user->employee->full_name);
                                        $set('position', $user->employee->qualification ?? $user->employee->position?->title);
                                    }
                                }
                            })
                            ->helperText('Lier à un utilisateur existant (optionnel)'),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Nom Complet')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('position')
                            ->label('Fonction/Titre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Directeur Général, Chef Service RH'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Type de Document et Ordre')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Type de Document')
                            ->options([
                                'leave_decision' => 'Décision de Congé',
                                'payroll' => 'Bulletin de Paie',
                                'contract' => 'Contrat',
                                'procurement_contract' => 'Contrat Marché Public',
                                'advancement_decision' => 'Décision Avancement',
                                'disciplinary_decision' => 'Décision Disciplinaire',
                                'all' => 'Tous les Documents',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Type de document que cette personne signera'),

                        Forms\Components\TextInput::make('signature_order')
                            ->label('Ordre de Signature')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->helperText('1 = première signature, 2 = deuxième, etc.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Signature et Cachet')
                    ->schema([
                        Forms\Components\FileUpload::make('signature_path')
                            ->label('Image de la Signature')
                            ->directory('signatures')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->helperText('Format PNG transparent recommandé, max 2MB'),

                        Forms\Components\FileUpload::make('stamp_path')
                            ->label('Cachet/Tampon')
                            ->directory('stamps')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->helperText('Image du cachet officiel, format PNG transparent recommandé'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statut et Notes')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Désactiver si le signataire n\'est plus en fonction'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Fonction')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('document_type')
                    ->label('Type de Document')
                    ->colors([
                        'primary' => 'leave_decision',
                        'success' => 'payroll',
                        'info' => 'contract',
                        'warning' => 'procurement_contract',
                        'secondary' => 'all',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'leave_decision' => 'Congé',
                        'payroll' => 'Paie',
                        'contract' => 'Contrat',
                        'procurement_contract' => 'Marché Public',
                        'advancement_decision' => 'Avancement',
                        'disciplinary_decision' => 'Disciplinaire',
                        'all' => 'Tous',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('signature_order')
                    ->label('Ordre')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\ImageColumn::make('signature_path')
                    ->label('Signature')
                    ->size(60)
                    ->toggleable(),

                Tables\Columns\ImageColumn::make('stamp_path')
                    ->label('Cachet')
                    ->size(60)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('signature_order')
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Type de Document')
                    ->options([
                        'leave_decision' => 'Congé',
                        'payroll' => 'Paie',
                        'contract' => 'Contrat',
                        'procurement_contract' => 'Marché Public',
                        'advancement_decision' => 'Avancement',
                        'disciplinary_decision' => 'Disciplinaire',
                        'all' => 'Tous',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignatories::route('/'),
            'create' => Pages\CreateSignatory::route('/create'),
            'edit' => Pages\EditSignatory::route('/{record}/edit'),
        ];
    }
}
