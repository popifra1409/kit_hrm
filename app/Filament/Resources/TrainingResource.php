<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingResource\Pages;
use App\Filament\Resources\TrainingResource\RelationManagers;
use App\Models\Training;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function getModelLabel(): string
    {
        return 'Formation';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Formations';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 9;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Formation')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations Générales')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titre de la Formation')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code Formation')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ex: FORM-2025-001'),

                                Forms\Components\Select::make('type')
                                    ->label('Type de Formation')
                                    ->options([
                                        'internal' => '🏢 Formation Interne',
                                        'external' => '🌍 Formation Externe',
                                        'online' => '💻 Formation en Ligne',
                                        'workshop' => '🛠️ Atelier',
                                        'seminar' => '📢 Séminaire',
                                        'certification' => '🎓 Certification',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('category')
                                    ->label('Catégorie')
                                    ->placeholder('Ex: Informatique, Santé, Management, Sécurité...')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'planned' => 'Planifiée',
                                        'registration_open' => 'Inscriptions Ouvertes',
                                        'registration_closed' => 'Inscriptions Fermées',
                                        'in_progress' => 'En Cours',
                                        'completed' => 'Terminée',
                                        'cancelled' => 'Annulée',
                                    ])
                                    ->default('planned')
                                    ->required()
                                    ->native(false),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Planning et Lieu')
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de Début')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de Fin')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->after('start_date'),

                                Forms\Components\TextInput::make('duration_hours')
                                    ->label('Durée (heures)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('heures'),

                                Forms\Components\TextInput::make('duration_days')
                                    ->label('Durée (jours)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('jours'),

                                Forms\Components\Toggle::make('is_online')
                                    ->label('Formation en Ligne')
                                    ->reactive()
                                    ->default(false),

                                Forms\Components\TextInput::make('online_link')
                                    ->label('Lien de la Formation')
                                    ->url()
                                    ->maxLength(255)
                                    ->visible(fn($get) => $get('is_online'))
                                    ->placeholder('https://zoom.us/...'),

                                Forms\Components\TextInput::make('location')
                                    ->label('Lieu')
                                    ->maxLength(255)
                                    ->visible(fn($get) => !$get('is_online'))
                                    ->placeholder('Ex: Salle de conférence, Hôtel...'),

                                Forms\Components\TextInput::make('room')
                                    ->label('Salle')
                                    ->maxLength(255)
                                    ->visible(fn($get) => !$get('is_online')),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Formateur et Organisation')
                            ->schema([
                                Forms\Components\TextInput::make('trainer_name')
                                    ->label('Nom du Formateur')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('training_organization')
                                    ->label('Organisme de Formation')
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('trainer_bio')
                                    ->label('Biographie du Formateur')
                                    ->rows(3)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('coordinator_id')
                                    ->label('Coordinateur')
                                    ->relationship('coordinator', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Participants et Budget')
                            ->schema([
                                Forms\Components\TextInput::make('min_participants')
                                    ->label('Nombre Minimum de Participants')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),

                                Forms\Components\TextInput::make('max_participants')
                                    ->label('Nombre Maximum de Participants')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Laisser vide pour illimité'),

                                Forms\Components\TextInput::make('cost_per_participant')
                                    ->label('Coût par Participant')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0),

                                Forms\Components\TextInput::make('total_budget')
                                    ->label('Budget Total')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0),

                                Forms\Components\TextInput::make('budget_source')
                                    ->label('Source du Financement')
                                    ->maxLength(255)
                                    ->placeholder('Ex: Budget RH, Fonds propres, Partenaire...'),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Contenu Pédagogique')
                            ->schema([
                                Forms\Components\Textarea::make('objectives')
                                    ->label('Objectifs de la Formation')
                                    ->rows(3)
                                    ->maxLength(65535)
                                    ->placeholder('Lister les objectifs pédagogiques...')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('prerequisites')
                                    ->label('Prérequis')
                                    ->rows(2)
                                    ->maxLength(65535)
                                    ->placeholder('Connaissances ou expériences requises...')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('program')
                                    ->label('Programme Détaillé')
                                    ->rows(5)
                                    ->maxLength(65535)
                                    ->placeholder('Détailler le contenu de la formation jour par jour...')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('materials_needed')
                                    ->label('Matériel Nécessaire')
                                    ->rows(2)
                                    ->maxLength(65535)
                                    ->placeholder('Ordinateur, projecteur, matériel spécifique...')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('materials_provided')
                                    ->label('Matériel Fourni')
                                    ->rows(2)
                                    ->maxLength(65535)
                                    ->placeholder('Documents, supports de cours, clés USB...')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Évaluation et Certification')
                            ->schema([
                                Forms\Components\Toggle::make('has_evaluation')
                                    ->label('Évaluation des Participants')
                                    ->default(false),

                                Forms\Components\Toggle::make('has_certificate')
                                    ->label('Délivrance de Certificat')
                                    ->reactive()
                                    ->default(false),

                                Forms\Components\FileUpload::make('certificate_template')
                                    ->label('Modèle de Certificat')
                                    ->directory('trainings/certificates')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->visible(fn($get) => $get('has_certificate'))
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('syllabus_document')
                                    ->label('Syllabus / Programme (PDF)')
                                    ->directory('trainings/syllabus')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(5120)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(5)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('report_document')
                                    ->label('Rapport de Formation (PDF)')
                                    ->directory('trainings/reports')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(5120)
                                    ->helperText('Rapport post-formation')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->title),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->getTypeLabel())
                    ->colors([
                        'primary' => 'internal',
                        'success' => 'external',
                        'info' => 'online',
                        'warning' => 'workshop',
                        'secondary' => 'seminar',
                        'danger' => 'certification',
                    ]),

                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Durée')
                    ->formatStateUsing(fn($record) => $record->duration_days . 'j / ' . $record->duration_hours . 'h')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->formatStateUsing(fn($record) => $record->getParticipantsCount() . ($record->max_participants ? ' / ' . $record->max_participants : ''))
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->color(fn($record) => $record->getStatusColor()),

                Tables\Columns\IconColumn::make('has_certificate')
                    ->label('Certificat')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'internal' => 'Formation Interne',
                        'external' => 'Formation Externe',
                        'online' => 'Formation en Ligne',
                        'workshop' => 'Atelier',
                        'seminar' => 'Séminaire',
                        'certification' => 'Certification',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'planned' => 'Planifiée',
                        'registration_open' => 'Inscriptions Ouvertes',
                        'registration_closed' => 'Inscriptions Fermées',
                        'in_progress' => 'En Cours',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ]),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Formations à venir')
                    ->query(fn($query) => $query->where('start_date', '>=', now())),
            ])
            ->actions([
                // Tables\Actions\Action::make('participants')
                //     ->label('Participants')
                //     ->icon('heroicon-o-users')
                //     ->url(fn($record) => route('filament.admin.resources.trainings.participants', $record))
                //     ->color('primary'),

                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
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
            RelationManagers\ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'edit' => Pages\EditTraining::route('/{record}/edit'),
            //'participants' => Pages\ManageParticipants::route('/{record}/participants'),
        ];
    }
}