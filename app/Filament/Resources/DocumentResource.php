<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return 'Document';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Documents';
    }

    public static function getNavigationGroup(): ?string
    {
        return '📚 Gestion Documentaire';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Numéro de Référence')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: DOC-2025-001'),

                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\Select::make('type')
                            ->label('Type de Document')
                            ->options([
                                'statute' => 'Statut',
                                'regulation' => 'Règlement Intérieur',
                                'policy' => 'Politique/Procédure',
                                'memo' => 'Note de Service',
                                'circular' => 'Circulaire',
                                'announcement' => 'Communiqué',
                                'contract_template' => 'Modèle de Contrat',
                                'form' => 'Formulaire',
                                'report' => 'Rapport',
                                'other' => 'Autre',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'review' => 'En Révision',
                                'approved' => 'Approuvé',
                                'published' => 'Publié',
                                'archived' => 'Archivé',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('summary')
                            ->label('Résumé')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Fichier')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Fichier')
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('file_name', $state->getClientOriginalName());
                                    $set('file_type', $state->getClientOriginalExtension());
                                    $set('file_size', $state->getSize());
                                }
                            }),

                        Forms\Components\TextInput::make('version')
                            ->label('Version')
                            ->default('1.0')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Date d\'Émission')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Date d\'Entrée en Vigueur')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Date d\'Expiration')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Visibilité et Accès')
                    ->schema([
                        Forms\Components\Select::make('visibility')
                            ->label('Visibilité')
                            ->options([
                                'public' => 'Public (Tous les employés)',
                                'restricted' => 'Restreint (Certains rôles/départements)',
                                'confidential' => 'Confidentiel (Accès limité)',
                            ])
                            ->default('public')
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\TagsInput::make('allowed_roles')
                            ->label('Rôles Autorisés')
                            ->placeholder('Ajouter un rôle...')
                            ->visible(fn($get) => $get('visibility') === 'restricted'),

                        Forms\Components\TagsInput::make('allowed_departments')
                            ->label('Départements Autorisés')
                            ->placeholder('Ajouter un département...')
                            ->visible(fn($get) => $get('visibility') === 'restricted'),

                        Forms\Components\Toggle::make('requires_acknowledgment')
                            ->label('Nécessite un Accusé de Lecture')
                            ->default(false)
                            ->helperText('Les employés devront confirmer avoir lu ce document'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Signature')
                    ->schema([
                        Forms\Components\Select::make('signed_by')
                            ->label('Signé par')
                            ->relationship('signedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\DatePicker::make('signed_date')
                            ->label('Date de Signature')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Métadonnées')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Ajouter des tags...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Référence')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->title),

                Tables\Columns\BadgeColumn::make('category.name')
                    ->label('Catégorie')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->getTypeLabel())
                    ->colors([
                        'danger' => 'statute',
                        'warning' => 'regulation',
                        'primary' => 'policy',
                        'info' => 'memo',
                        'success' => 'circular',
                        'secondary' => fn($state) => in_array($state, ['announcement', 'contract_template', 'form', 'report', 'other']),
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->color(fn($record) => $record->getStatusColor()),

                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date Effective')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('Téléchargements')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('Vues')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'statute' => 'Statut',
                        'regulation' => 'Règlement Intérieur',
                        'policy' => 'Politique/Procédure',
                        'memo' => 'Note de Service',
                        'circular' => 'Circulaire',
                        'announcement' => 'Communiqué',
                        'contract_template' => 'Modèle de Contrat',
                        'form' => 'Formulaire',
                        'report' => 'Rapport',
                        'other' => 'Autre',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'review' => 'En Révision',
                        'approved' => 'Approuvé',
                        'published' => 'Publié',
                        'archived' => 'Archivé',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        $record->incrementDownloadCount();
                        return Storage::download($record->file_path, $record->file_name);
                    }),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
