<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;
use App\Models\Employee;
use App\Models\User;

class HospitalSettings extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Configuration Hôpital';
    protected static string $view = 'filament.pages.hospital-settings';
    protected static ?string $title = 'Configuration de l\'Hôpital';
    protected static ?string $navigationGroup = '⚙️ Paramétrage';
    protected static ?int $navigationSort = 3;

    // Propriétés du formulaire
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Informations générales
            'hospital_name' => SystemSetting::get('hospital_name', 'CENTRE HOSPITALIER ET UNIVERSITAIRE DE YAOUNDÉ'),
            'hospital_short_name' => SystemSetting::get('hospital_short_name', 'CHUY'),
            'hospital_slogan' => SystemSetting::get('hospital_slogan'),
            'hospital_description' => SystemSetting::get('hospital_description'),

            // Coordonnées
            'hospital_address' => SystemSetting::get('hospital_address'),
            'hospital_city' => SystemSetting::get('hospital_city', 'Yaoundé'),
            'hospital_country' => SystemSetting::get('hospital_country', 'Cameroun'),
            'hospital_postal_code' => SystemSetting::get('hospital_postal_code'),
            'hospital_phone' => SystemSetting::get('hospital_phone'),
            'hospital_fax' => SystemSetting::get('hospital_fax'),
            'hospital_email' => SystemSetting::get('hospital_email'),
            'hospital_website' => SystemSetting::get('hospital_website'),

            // Identifiants légaux
            'hospital_registration_number' => SystemSetting::get('hospital_registration_number'),
            'hospital_tax_number' => SystemSetting::get('hospital_tax_number'),
            'hospital_cnps_number' => SystemSetting::get('hospital_cnps_number'),

            // Images - décoder le JSON si nécessaire
            // Comptes utilisateurs
            'default_temporary_password' => SystemSetting::get('default_temporary_password', 'Hgy@2026!'),

            'hospital_logo' => $this->decodeIfJson(SystemSetting::get('hospital_logo')),
            'hospital_favicon' => $this->decodeIfJson(SystemSetting::get('hospital_favicon')),
            'hospital_stamp' => $this->decodeIfJson(SystemSetting::get('hospital_stamp')),
            'hospital_header_image' => $this->decodeIfJson(SystemSetting::get('hospital_header_image')),
        ]);
    }

    protected function decodeIfJson($value)
    {
        if (is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    protected function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configuration')
                    ->tabs([
                        Tabs\Tab::make('Informations Générales')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('hospital_name')
                                            ->label('Nom Complet de la structure')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Ex: Kisin Information Technology'),

                                        TextInput::make('hospital_short_name')
                                            ->label('Nom Court / Sigle')
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Ex: KIT'),

                                        TextInput::make('hospital_slogan')
                                            ->label('Slogan / Devise')
                                            ->maxLength(255)
                                            ->placeholder('Ex: Excellence et Humanité dans les Soins'),

                                        Textarea::make('hospital_description')
                                            ->label('Description')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->placeholder('Brève description de la strucutre')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Coordonnées')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Adresse Physique')
                                    ->schema([
                                        Textarea::make('hospital_address')
                                            ->label('Adresse Complète')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Ville, Quartier...')
                                            ->columnSpanFull(),

                                        TextInput::make('hospital_city')
                                            ->label('Ville')
                                            ->default('Yaoundé')
                                            ->maxLength(100),

                                        TextInput::make('hospital_country')
                                            ->label('Pays')
                                            ->default('Cameroun')
                                            ->maxLength(100),

                                        TextInput::make('hospital_postal_code')
                                            ->label('Code Postal / BP')
                                            ->maxLength(50)
                                            ->placeholder('Ex: BP 1234'),
                                    ])
                                    ->columns(3),

                                Section::make('Contacts')
                                    ->schema([
                                        TextInput::make('hospital_phone')
                                            ->label('Téléphone Principal')
                                            ->tel()
                                            ->maxLength(50)
                                            ->placeholder('+237 222 XX XX XX'),

                                        TextInput::make('hospital_fax')
                                            ->label('Fax')
                                            ->tel()
                                            ->maxLength(50)
                                            ->placeholder('+237 222 XX XX XX'),

                                        TextInput::make('hospital_email')
                                            ->label('Email Principal')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('contact@hospital.cm'),

                                        TextInput::make('hospital_website')
                                            ->label('Site Web')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://www.chuy.cm'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Identifiants Légaux')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('hospital_registration_number')
                                            ->label('Numéro d\'Enregistrement')
                                            ->maxLength(255)
                                            ->placeholder('Numéro d\'agrément ou d\'enregistrement'),

                                        TextInput::make('hospital_tax_number')
                                            ->label('Numéro Contribuable')
                                            ->maxLength(255)
                                            ->placeholder('Ex: M062900000000'),

                                        TextInput::make('hospital_cnps_number')
                                            ->label('Numéro Employeur CNPS')
                                            ->maxLength(255)
                                            ->placeholder('Ex: 3210100565'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tabs\Tab::make('Comptes Utilisateurs')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Mot de Passe Temporaire par Défaut')
                                    ->description("Utilisé lors de la création en masse des comptes employés (bouton en haut de page). Chaque employé devra ensuite l'utiliser, avec son matricule, pour activer son compte et choisir son propre mot de passe définitif.")
                                    ->schema([
                                        TextInput::make('default_temporary_password')
                                            ->label('Mot de passe temporaire par défaut')
                                            ->required()
                                            ->minLength(8)
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->helperText('Minimum 8 caractères. Communiquez-le aux employés avec leur matricule pour la première connexion.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Images et Logos')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        FileUpload::make('hospital_logo')
                                            ->label('Logo de la structure')
                                            ->image()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('hospital')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                            ->openable()
                                            ->downloadable()
                                            ->helperText('Logo principal (format PNG transparent recommandé, max 2MB). Cliquez sur la vignette pour remplacer ou retirer l\'image.')
                                            ->columnSpanFull(),

                                        FileUpload::make('hospital_favicon')
                                            ->label('Icône de la structure')
                                            ->image()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('hospital')
                                            ->maxSize(512)
                                            ->openable()
                                            ->downloadable()
                                            ->helperText('Icône dans l\'onglet du navigateur (32x32px)')
                                            ->columnSpanFull(),

                                        FileUpload::make('hospital_stamp')
                                            ->label('Cachet Officiel')
                                            ->image()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('hospital')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                            ->openable()
                                            ->downloadable()
                                            ->helperText('Cachet pour les documents officiels (PNG transparent recommandé). Cliquez sur la vignette pour remplacer ou retirer l\'image.')
                                            ->columnSpanFull(),

                                        FileUpload::make('hospital_header_image')
                                            ->label('Image d\'En-tête Documents')
                                            ->image()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('hospital')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                            ->openable()
                                            ->downloadable()
                                            ->helperText('En-tête pour les documents PDF (recommandé: 1200x200px). Cliquez sur la vignette pour remplacer ou retirer l\'image.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                if ($value !== null) {
                    // Convertir les tableaux en string pour les fichiers
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }

                    SystemSetting::set($key, $value, 'hospital', $this->getFieldType($key));
                }
            }

            // Vider le cache
            SystemSetting::clearCache();

            Notification::make()
                ->title('Configuration enregistrée')
                ->success()
                ->body('Les paramètres de l\'hôpital ont été enregistrés avec succès.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->send();
        }
    }

    protected function getFieldType($key): string
    {
        if (in_array($key, ['hospital_logo', 'hospital_favicon', 'hospital_stamp', 'hospital_header_image'])) {
            return 'file';
        }

        return 'text';
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Enregistrer la Configuration')
                ->submit('save')
                ->color('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_create_accounts')
                ->label('Créer les comptes employés manquants')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn() => auth()->user()?->can('create_users') ?? false)
                ->requiresConfirmation()
                ->modalHeading('Créer les comptes utilisateurs manquants')
                ->modalDescription(function () {
                    $count = Employee::where('is_active', true)->whereDoesntHave('user')->count();
                    return "Un compte sera créé pour chacun des {$count} employé(s) actif(s) n'en ayant pas encore, avec le mot de passe temporaire défini dans l'onglet « Comptes Utilisateurs » (pensez à l'enregistrer avant de lancer cette action si vous venez de le modifier). Chaque employé devra ensuite l'activer avec son matricule.";
                })
                ->modalSubmitActionLabel('Créer les comptes')
                ->action(function () {
                    // Cette action peut traiter plusieurs centaines d'employés ; on retire
                    // la limite de temps par défaut (30s) par sécurité.
                    set_time_limit(300);

                    $defaultPassword = SystemSetting::get('default_temporary_password');

                    if (!$defaultPassword || strlen($defaultPassword) < 8) {
                        Notification::make()
                            ->title('Mot de passe temporaire manquant ou trop court')
                            ->body('Renseignez et enregistrez un mot de passe temporaire par défaut (8 caractères minimum) dans l\'onglet « Comptes Utilisateurs » avant de lancer cette action.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // ⚠️ Point critique de performance : bcrypt est volontairement lent
                    // (~100-300ms par hachage). Avec des centaines d'employés, le hacher
                    // une fois par utilisateur dans la boucle dépasse largement les 30
                    // secondes d'exécution PHP par défaut. On le hache UNE SEULE FOIS ici,
                    // puis on réutilise le même hash pour tous : le cast 'hashed' du modèle
                    // User détecte qu'une valeur déjà hachée n'a pas besoin de l'être à
                    // nouveau (Hash::needsRehash() renvoie false), donc rien n'est cassé.
                    $hashedPassword = \Illuminate\Support\Facades\Hash::make($defaultPassword);

                    $created = 0;
                    $skippedNoMatricule = 0;
                    $failed = 0;
                    $firstError = null;

                    Employee::where('is_active', true)
                        ->whereDoesntHave('user')
                        ->chunkById(100, function ($employees) use ($hashedPassword, &$created, &$skippedNoMatricule, &$failed, &$firstError) {
                            foreach ($employees as $employee) {
                                if (!$employee->matricule) {
                                    $skippedNoMatricule++;
                                    continue;
                                }

                                try {
                                    $user = User::create([
                                        'employee_id' => $employee->id,
                                        'name' => $employee->full_name,
                                        'email' => $employee->email,
                                        'password' => $hashedPassword,
                                        'activated_at' => null,
                                    ]);

                                    $user->assignRole('employee');
                                    $created++;
                                } catch (\Throwable $e) {
                                    $failed++;
                                    \Illuminate\Support\Facades\Log::error('Échec création compte employé (recensement en masse)', [
                                        'employee_id' => $employee->id,
                                        'matricule' => $employee->matricule,
                                        'error' => $e->getMessage(),
                                    ]);
                                    $firstError ??= $e->getMessage();
                                }
                            }
                        });

                    $body = "{$created} compte(s) créé(s).";
                    if ($skippedNoMatricule > 0) {
                        $body .= " {$skippedNoMatricule} employé(s) ignoré(s) (matricule manquant).";
                    }
                    if ($failed > 0) {
                        $body .= " {$failed} échec(s). Première erreur : {$firstError}";
                    }

                    Notification::make()
                        ->title('Création des comptes terminée')
                        ->body($body)
                        ->color($failed > 0 ? 'warning' : 'success')
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
