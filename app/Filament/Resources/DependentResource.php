<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DependentResource\Pages;
use App\Models\Dependent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Enums\ActionsPosition;

class DependentResource extends Resource
{
    protected static ?string $model = Dependent::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return 'Ayant Droit';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ayants Droit & Assurance';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ... votre formulaire reste identique
                Forms\Components\Section::make('Employé')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'matricule')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name . ' (' . $record->matricule . ')'),
                    ]),

                Forms\Components\Section::make('Informations Personnelles')
                    ->schema([
                        Forms\Components\Select::make('relationship')
                            ->label('Type d\'Ayant Droit')
                            ->options([
                                'spouse' => '💑 Conjoint(e)',
                                'child' => '👶 Enfant',
                                'father' => '👨 Père',
                                'mother' => '👩 Mère',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Prénom(s)')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Date de Naissance')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        Forms\Components\TextInput::make('birth_place')
                            ->label('Lieu de Naissance')
                            ->maxLength(255),

                        Forms\Components\Select::make('gender')
                            ->label('Sexe')
                            ->options([
                                'M' => 'Masculin',
                                'F' => 'Féminin',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Documents Justificatifs')
                    ->schema([
                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->directory('dependents/photos')
                            ->maxSize(2048)
                            ->imageEditor(),

                        Forms\Components\FileUpload::make('id_card_path')
                            ->label('Carte d\'Identité')
                            ->directory('dependents/documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120),

                        Forms\Components\FileUpload::make('birth_certificate_path')
                            ->label('Acte de Naissance')
                            ->directory('dependents/documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->required(),

                        Forms\Components\FileUpload::make('marriage_certificate_path')
                            ->label('Acte de Mariage')
                            ->directory('dependents/documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->visible(fn($get) => $get('relationship') === 'spouse')
                            ->required(fn($get) => $get('relationship') === 'spouse'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Prise en Charge')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif dans le Système')
                            ->default(true),

                        Forms\Components\TextInput::make('coverage_rate')
                            ->label('Taux de Couverture')
                            ->numeric()
                            ->suffix('%')
                            ->default(75.00)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required(),

                        Forms\Components\DatePicker::make('coverage_start_date')
                            ->label('Début de Couverture')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('coverage_end_date')
                            ->label('Fin de Couverture')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('coverage_start_date'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Carte de Prise en Charge')
                    ->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->label('Numéro de Carte')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('card_issued')
                            ->label('Carte Émise')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('card_issue_date')
                            ->label('Date d\'Émission')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('card_expiry_date')
                            ->label('Date d\'Expiration')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Toggle::make('card_active')
                            ->label('Carte Active')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->visible(fn($context) => $context === 'edit'),

                Forms\Components\Section::make('Statut Vital')
                    ->schema([
                        Forms\Components\Toggle::make('is_alive')
                            ->label('En Vie')
                            ->default(true)
                            ->reactive(),

                        Forms\Components\DatePicker::make('death_date')
                            ->label('Date de Décès')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn($get) => !$get('is_alive')),

                        Forms\Components\FileUpload::make('death_certificate_path')
                            ->label('Acte de Décès')
                            ->directory('dependents/documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->visible(fn($get) => !$get('is_alive')),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Validation RH')
                    ->description('Statut de validation après vérification des documents physiques par les RH')
                    ->schema([
                        Forms\Components\Select::make('validation_status')
                            ->label('Statut')
                            ->options([
                                'pending' => '⏳ En attente',
                                'validated' => '✅ Validé',
                                'rejected' => '❌ Rejeté',
                            ])
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('validatedBy.name')
                            ->label('Validé par')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('validated_at')
                            ->label('Date de validation')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn($get) => $get('validation_status') === 'rejected')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('submitted_via')
                            ->label('Origine de la saisie')
                            ->formatStateUsing(fn($state) => $state === 'mobile' ? '📱 App mobile (employé)' : '🖥️ Administration')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn($context) => $context === 'edit'),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('medical_notes')
                            ->label('Notes Médicales')
                            ->rows(2)
                            ->maxLength(65535),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observations')
                            ->rows(2)
                            ->maxLength(65535),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn(Dependent $record) =>
                        '📋 ' . $record->employee->matricule
                    )
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Ayant Droit')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('relationship')
                    ->label('Lien')
                    ->formatStateUsing(fn($record) => $record->getRelationshipLabel())
                    ->colors([
                        'danger' => 'spouse',
                        'success' => 'child',
                        'info' => 'father',
                        'warning' => 'mother',
                    ]),

                Tables\Columns\TextColumn::make('age')
                    ->label('Âge')
                    ->suffix(' ans')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('coverage_rate')
                    ->label('Taux')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('validation_status')
                    ->label('Statut RH')
                    ->formatStateUsing(fn($record) => $record->validation_status_label)
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'validated',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('card_issued')
                    ->label('Carte')
                    ->boolean()
                    ->trueIcon('heroicon-o-credit-card')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('card_active')
                    ->label('Carte Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_alive')
                    ->label('Vivant')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            // ✅ SUPPRIMER ces lignes pour restaurer la pagination normale
            // ->groups([...])
            // ->defaultGroup('employee.full_name')
            // ->groupsOnly()
            ->filters([
                Tables\Filters\SelectFilter::make('validation_status')
                    ->label('Statut RH')
                    ->options([
                        'pending' => '⏳ En attente',
                        'validated' => '✅ Validé',
                        'rejected' => '❌ Rejeté',
                    ]),

                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'matricule')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name . ' (' . $record->matricule . ')'),

                Tables\Filters\SelectFilter::make('relationship')
                    ->label('Type')
                    ->options([
                        'spouse' => 'Conjoint(e)',
                        'child' => 'Enfant',
                        'father' => 'Père',
                        'mother' => 'Mère',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),

                Tables\Filters\TernaryFilter::make('card_issued')
                    ->label('Carte Émise'),

                Tables\Filters\TernaryFilter::make('is_alive')
                    ->label('En Vie'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Voir'),
                    Tables\Actions\EditAction::make()->label('Modifier'),

                    Tables\Actions\Action::make('validate')
                        ->label('Valider')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(Dependent $record) => $record->isPending())
                        ->requiresConfirmation()
                        ->modalHeading('Valider cet ayant droit')
                        ->modalDescription('Confirmez-vous avoir vérifié les documents physiques justificatifs ?')
                        ->action(fn(Dependent $record) => $record->validate()),

                    Tables\Actions\Action::make('reject')
                        ->label('Rejeter')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(Dependent $record) => $record->isPending())
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif du rejet')
                                ->required(),
                        ])
                        ->action(fn(Dependent $record, array $data) => $record->reject($data['reason'])),

                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('validate_bulk')
                        ->label('Valider la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn(Dependent $record) => $record->isPending() && $record->validate());
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDependents::route('/'),
            'tree' => Pages\DependentsTree::route('/tree'),
            'create' => Pages\CreateDependent::route('/create'),
            'edit' => Pages\EditDependent::route('/{record}/edit'),
        ];
    }
}
