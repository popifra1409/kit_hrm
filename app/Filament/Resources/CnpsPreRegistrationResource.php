<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CnpsPreRegistrationResource\Pages;
use App\Models\CnpsPreRegistration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CnpsPreRegistrationResource extends Resource
{
    protected static ?string $model = CnpsPreRegistration::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function getModelLabel(): string
    {
        return 'Pré-immatriculation CNPS';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pré-immatriculations CNPS';
    }

    public static function getNavigationGroup(): ?string
    {
        return '📚 Gestion Documentaire';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Employé')
                        ->schema([
                            Forms\Components\Select::make('employee_id')
                                ->label('Employé')
                                ->options(function () {
                                    return \App\Models\Employee::query()
                                        ->where('is_active', true)
                                        ->whereNull('cnps_number') // Seulement ceux sans numéro CNPS
                                        ->get()
                                        ->mapWithKeys(fn($employee) => [
                                            $employee->id => $employee->full_name . ' (' . $employee->matricule . ')'
                                        ]);
                                })
                                ->searchable()
                                ->required()
                                ->preload()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $employee = \App\Models\Employee::find($state);
                                    if ($employee) {
                                        $set('first_name', $employee->first_name);
                                        $set('last_name', $employee->last_name);
                                        $set('birth_date', $employee->birth_date);
                                        $set('birth_place', $employee->birth_place);
                                        $set('gender', $employee->gender);
                                        $set('phone', $employee->phone);
                                        $set('email', $employee->email);
                                        $set('position_title', $employee->position?->title);
                                        $set('hire_date', $employee->hire_date);
                                        $set('address', $employee->address);
                                    }
                                })
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Wizard\Step::make('Informations Personnelles')
                        ->schema([
                            Forms\Components\TextInput::make('first_name')
                                ->label('Prénom(s)')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('last_name')
                                ->label('Nom')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\DatePicker::make('birth_date')
                                ->label('Date de Naissance')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TextInput::make('birth_place')
                                ->label('Lieu de Naissance')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('gender')
                                ->label('Sexe')
                                ->options([
                                    'M' => 'Masculin',
                                    'F' => 'Féminin',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('nationality')
                                ->label('Nationalité')
                                ->default('Camerounaise')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Pièce d\'Identité')
                        ->schema([
                            Forms\Components\Select::make('id_type')
                                ->label('Type de Pièce')
                                ->options([
                                    'cni' => 'Carte Nationale d\'Identité',
                                    'passport' => 'Passeport',
                                    'residence_permit' => 'Titre de Séjour',
                                ])
                                ->default('cni')
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('id_number')
                                ->label('Numéro de Pièce')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\DatePicker::make('id_issue_date')
                                ->label('Date de Délivrance')
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\DatePicker::make('id_expiry_date')
                                ->label('Date d\'Expiration')
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\FileUpload::make('id_document_path')
                                ->label('Scan de la Pièce d\'Identité')
                                ->directory('cnps/identity-documents')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Adresse et Contact')
                        ->schema([
                            Forms\Components\TextInput::make('address')
                                ->label('Adresse')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('city')
                                ->label('Ville')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('region')
                                ->label('Région')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('phone')
                                ->label('Téléphone')
                                ->tel()
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Situation Familiale')
                        ->schema([
                            Forms\Components\Select::make('marital_status')
                                ->label('Situation Matrimoniale')
                                ->options([
                                    'single' => 'Célibataire',
                                    'married' => 'Marié(e)',
                                    'divorced' => 'Divorcé(e)',
                                    'widowed' => 'Veuf/Veuve',
                                ])
                                ->default('single')
                                ->required()
                                ->reactive()
                                ->native(false),

                            Forms\Components\TextInput::make('number_of_children')
                                ->label('Nombre d\'Enfants')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\FileUpload::make('marriage_certificate_path')
                                ->label('Certificat de Mariage')
                                ->directory('cnps/marriage-certificates')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->visible(fn($get) => $get('marital_status') === 'married')
                                ->required(fn($get) => $get('marital_status') === 'married')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Informations Professionnelles')
                        ->schema([
                            Forms\Components\TextInput::make('position_title')
                                ->label('Poste Occupé')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\DatePicker::make('hire_date')
                                ->label('Date d\'Embauche')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TextInput::make('monthly_salary')
                                ->label('Salaire Mensuel')
                                ->numeric()
                                ->prefix('FCFA')
                                ->required(),

                            Forms\Components\Select::make('contract_type')
                                ->label('Type de Contrat')
                                ->options([
                                    'permanent' => 'CDI',
                                    'fixed_term' => 'CDD',
                                    'temporary' => 'Temporaire',
                                ])
                                ->default('permanent')
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('cnps_category')
                                ->label('Catégorie CNPS')
                                ->options([
                                    'cadre_superieur' => 'Cadre Supérieur',
                                    'cadre_moyen' => 'Cadre Moyen',
                                    'agent_maitrise' => 'Agent de Maîtrise',
                                    'employe_qualifie' => 'Employé Qualifié',
                                    'employe' => 'Employé',
                                    'manoeuvre' => 'Manoeuvre',
                                ])
                                ->default('employe')
                                ->required()
                                ->native(false),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Contact d\'Urgence')
                        ->schema([
                            Forms\Components\TextInput::make('emergency_contact_name')
                                ->label('Nom du Contact')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('emergency_contact_relationship')
                                ->label('Lien de Parenté')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('emergency_contact_phone')
                                ->label('Téléphone')
                                ->tel()
                                ->maxLength(255),
                        ])
                        ->columns(3),

                    Forms\Components\Wizard\Step::make('Documents')
                        ->schema([
                            Forms\Components\FileUpload::make('birth_certificate_path')
                                ->label('Acte de Naissance')
                                ->directory('cnps/birth-certificates')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->required(),

                            Forms\Components\FileUpload::make('children_birth_certificates_path')
                                ->label('Actes de Naissance des Enfants')
                                ->directory('cnps/children-certificates')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->multiple(),

                            Forms\Components\FileUpload::make('medical_certificate_path')
                                ->label('Certificat Médical')
                                ->directory('cnps/medical-certificates')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120),

                            Forms\Components\FileUpload::make('photo_path')
                                ->label('Photo d\'Identité')
                                ->directory('cnps/photos')
                                ->image()
                                ->maxSize(2048)
                                ->required(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Informations Employeur')
                        ->schema([
                            Forms\Components\TextInput::make('employer_name')
                                ->label('Nom de l\'Employeur')
                                ->default('Centre Hospitalier Universitaire de Yaoundé')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('employer_cnps_number')
                                ->label('N° CNPS Employeur')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('employer_address')
                                ->label('Adresse Employeur')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('employer_phone')
                                ->label('Téléphone Employeur')
                                ->tel()
                                ->maxLength(255),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull()
                    //->submitAction(view('filament.pages.actions.wizard-submit')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cnps_number')
                    ->label('N° CNPS')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('position_title')
                    ->label('Poste')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Date d\'Embauche')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->color(fn($record) => $record->getStatusColor()),

                Tables\Columns\IconColumn::make('documents_complete')
                    ->label('Documents')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->hasAllRequiredDocuments())
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'pending' => 'En Attente',
                        'submitted' => 'Soumise',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        'completed' => 'Terminée',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->validate())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Demande validée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('submit')
                    ->label('Soumettre à CNPS')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->submit())
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Demande soumise à la CNPS')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'submitted')
                    ->form([
                        Forms\Components\TextInput::make('cnps_number')
                            ->label('Numéro CNPS')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data) {
                        $record->approve($data['cnps_number']);

                        \Filament\Notifications\Notification::make()
                            ->title('Immatriculation approuvée')
                            ->success()
                            ->body('N° CNPS: ' . $data['cnps_number'])
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => in_array($record->status, ['pending', 'submitted']))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif du Rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Demande rejetée')
                            ->danger()
                            ->send();
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
            'index' => Pages\ListCnpsPreRegistrations::route('/'),
            'create' => Pages\CreateCnpsPreRegistration::route('/create'),
            'edit' => Pages\EditCnpsPreRegistration::route('/{record}/edit'),
        ];
    }
}
