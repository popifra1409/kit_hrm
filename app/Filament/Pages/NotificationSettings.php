<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Notifications';
    protected static string $view = 'filament.pages.notification-settings';
    protected static ?string $title = 'Configuration des Notifications';
    protected static ?string $navigationGroup = '⚙️ Paramétrage';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Email SMTP
            'email_enabled' => SystemSetting::get('email_enabled', true),
            'smtp_host' => SystemSetting::get('smtp_host'),
            'smtp_port' => SystemSetting::get('smtp_port', 587),
            'smtp_username' => SystemSetting::get('smtp_username'),
            'smtp_password' => SystemSetting::get('smtp_password'),
            'smtp_encryption' => SystemSetting::get('smtp_encryption', 'tls'),
            'smtp_from_address' => SystemSetting::get('smtp_from_address'),
            'smtp_from_name' => SystemSetting::get('smtp_from_name', 'CHUY - GRH'),

            // SMS
            'sms_enabled' => SystemSetting::get('sms_enabled', false),
            'sms_provider' => SystemSetting::get('sms_provider', 'none'),
            'sms_api_key' => SystemSetting::get('sms_api_key'),
            'sms_api_secret' => SystemSetting::get('sms_api_secret'),
            'sms_sender_name' => SystemSetting::get('sms_sender_name', 'CHUY'),

            // WhatsApp
            'whatsapp_enabled' => SystemSetting::get('whatsapp_enabled', false),
            'whatsapp_provider' => SystemSetting::get('whatsapp_provider', 'none'),
            'whatsapp_api_key' => SystemSetting::get('whatsapp_api_key'),
            'whatsapp_phone_number' => SystemSetting::get('whatsapp_phone_number'),

            // Notifications internes
            'system_notifications_enabled' => SystemSetting::get('system_notifications_enabled', true),
            'notification_retention_days' => SystemSetting::get('notification_retention_days', 90),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configuration Notifications')
                    ->tabs([
                        Tabs\Tab::make('Email (SMTP)')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('Activation Email')
                                    ->schema([
                                        Toggle::make('email_enabled')
                                            ->label('Activer les notifications par Email')
                                            ->default(true)
                                            ->reactive()
                                            ->helperText('Envoyer automatiquement des emails aux employés'),
                                    ]),

                                Section::make('Configuration Serveur SMTP')
                                    ->schema([
                                        TextInput::make('smtp_host')
                                            ->label('Serveur SMTP')
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->maxLength(255)
                                            ->placeholder('smtp.gmail.com ou smtp.office365.com')
                                            ->helperText('Adresse du serveur SMTP'),

                                        TextInput::make('smtp_port')
                                            ->label('Port SMTP')
                                            ->numeric()
                                            ->default(587)
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->helperText('587 pour TLS, 465 pour SSL, 25 pour non-sécurisé'),

                                        Select::make('smtp_encryption')
                                            ->label('Chiffrement')
                                            ->options([
                                                'tls' => 'TLS (recommandé)',
                                                'ssl' => 'SSL',
                                                'none' => 'Aucun',
                                            ])
                                            ->default('tls')
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->native(false),

                                        TextInput::make('smtp_username')
                                            ->label('Nom d\'utilisateur SMTP')
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->maxLength(255)
                                            ->placeholder('votre-email@domaine.com')
                                            ->helperText('Email de connexion au serveur SMTP'),

                                        TextInput::make('smtp_password')
                                            ->label('Mot de passe SMTP')
                                            ->password()
                                            ->revealable()
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->maxLength(255)
                                            ->helperText('Mot de passe ou mot de passe d\'application'),

                                        TextInput::make('smtp_from_address')
                                            ->label('Email d\'expédition')
                                            ->email()
                                            ->required(fn($get) => $get('email_enabled'))
                                            ->maxLength(255)
                                            ->placeholder('noreply@chuy.cm')
                                            ->helperText('Adresse email qui apparaîtra comme expéditeur'),

                                        TextInput::make('smtp_from_name')
                                            ->label('Nom d\'expédition')
                                            ->default('CHUY - GRH')
                                            ->maxLength(255)
                                            ->placeholder('CHUY - Gestion RH')
                                            ->helperText('Nom qui apparaîtra comme expéditeur'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn($get) => $get('email_enabled')),

                                Section::make('Exemples de Configuration SMTP')
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('gmail_example')
                                            ->label('Gmail')
                                            ->content('
                                                Serveur: smtp.gmail.com
                                                Port: 587
                                                Chiffrement: TLS
                                                Note: Utilisez un "mot de passe d\'application" (https://myaccount.google.com/apppasswords)
                                            '),

                                        \Filament\Forms\Components\Placeholder::make('office365_example')
                                            ->label('Office 365 / Outlook')
                                            ->content('
                                                Serveur: smtp.office365.com
                                                Port: 587
                                                Chiffrement: TLS
                                            '),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed()
                                    ->visible(fn($get) => $get('email_enabled')),
                            ]),

                        Tabs\Tab::make('SMS')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Section::make('Activation SMS')
                                    ->schema([
                                        Toggle::make('sms_enabled')
                                            ->label('Activer les notifications par SMS')
                                            ->default(false)
                                            ->reactive()
                                            ->helperText('Envoyer des SMS aux employés (nécessite un fournisseur)'),
                                    ]),

                                Section::make('Configuration SMS')
                                    ->schema([
                                        Select::make('sms_provider')
                                            ->label('Fournisseur SMS')
                                            ->options([
                                                'none' => 'Aucun',
                                                'twilio' => 'Twilio',
                                                'nexmo' => 'Vonage (Nexmo)',
                                                'africas_talking' => 'Africa\'s Talking',
                                                'custom' => 'API Personnalisée',
                                            ])
                                            ->default('none')
                                            ->required(fn($get) => $get('sms_enabled'))
                                            ->reactive()
                                            ->native(false),

                                        TextInput::make('sms_sender_name')
                                            ->label('Nom d\'expéditeur')
                                            ->default('CHUY')
                                            ->maxLength(11)
                                            ->placeholder('CHUY')
                                            ->helperText('Nom affiché (max 11 caractères)'),

                                        TextInput::make('sms_api_key')
                                            ->label('Clé API')
                                            ->required(fn($get) => $get('sms_enabled') && $get('sms_provider') !== 'none')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->visible(fn($get) => $get('sms_provider') !== 'none'),

                                        TextInput::make('sms_api_secret')
                                            ->label('Secret API')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->visible(fn($get) => in_array($get('sms_provider'), ['twilio', 'nexmo', 'africas_talking'])),
                                    ])
                                    ->columns(2)
                                    ->visible(fn($get) => $get('sms_enabled')),

                                Section::make('Fournisseurs SMS Populaires au Cameroun')
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('sms_providers_info')
                                            ->content('
                                                • Africa\'s Talking: https://africastalking.com (recommandé pour l\'Afrique)
                                                • Twilio: https://www.twilio.com (international)
                                                • Vonage (Nexmo): https://www.vonage.com
                                            '),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->visible(fn($get) => $get('sms_enabled')),
                            ]),

                        Tabs\Tab::make('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Activation WhatsApp')
                                    ->schema([
                                        Toggle::make('whatsapp_enabled')
                                            ->label('Activer les notifications par WhatsApp')
                                            ->default(false)
                                            ->reactive()
                                            ->helperText('Envoyer des messages WhatsApp (nécessite WhatsApp Business API)'),
                                    ]),

                                Section::make('Configuration WhatsApp')
                                    ->schema([
                                        Select::make('whatsapp_provider')
                                            ->label('Fournisseur WhatsApp')
                                            ->options([
                                                'none' => 'Aucun',
                                                'twilio' => 'Twilio WhatsApp',
                                                'whatsapp_cloud' => 'WhatsApp Cloud API (Meta)',
                                                'infobip' => 'Infobip',
                                                'custom' => 'API Personnalisée',
                                            ])
                                            ->default('none')
                                            ->required(fn($get) => $get('whatsapp_enabled'))
                                            ->reactive()
                                            ->native(false),

                                        TextInput::make('whatsapp_phone_number')
                                            ->label('Numéro WhatsApp Business')
                                            ->tel()
                                            ->placeholder('+237XXXXXXXXX')
                                            ->helperText('Numéro WhatsApp Business enregistré'),

                                        TextInput::make('whatsapp_api_key')
                                            ->label('Clé API / Token')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->visible(fn($get) => $get('whatsapp_provider') !== 'none'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn($get) => $get('whatsapp_enabled')),

                                Section::make('Configuration WhatsApp Business API')
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('whatsapp_info')
                                            ->content('
                                                Pour utiliser WhatsApp Business API, vous devez :
                                                1. Créer un compte Meta Business (business.facebook.com)
                                                2. Configurer WhatsApp Business API
                                                3. Obtenir un token d\'accès
                                                4. Vérifier votre numéro de téléphone
                                                
                                                Guide: https://developers.facebook.com/docs/whatsapp/cloud-api/get-started
                                            '),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->visible(fn($get) => $get('whatsapp_enabled')),
                            ]),

                        Tabs\Tab::make('Notifications Internes')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Section::make('Configuration')
                                    ->schema([
                                        Toggle::make('system_notifications_enabled')
                                            ->label('Activer les notifications internes')
                                            ->default(true)
                                            ->helperText('Afficher les notifications dans l\'application'),

                                        TextInput::make('notification_retention_days')
                                            ->label('Durée de conservation (jours)')
                                            ->numeric()
                                            ->default(90)
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->helperText('Nombre de jours avant suppression automatique des notifications lues'),
                                    ])
                                    ->columns(2),
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
                    $type = $this->getFieldType($key);
                    $group = $this->getFieldGroup($key);
                    SystemSetting::set($key, $value, $group, $type);
                }
            }

            SystemSetting::clearCache();

            Notification::make()
                ->title('Configuration enregistrée')
                ->success()
                ->body('Les paramètres de notification ont été enregistrés avec succès.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur lors de l\'enregistrement : ' . $e->getMessage())
                ->send();
        }
    }

    public function testEmail(): void
    {
        Notification::make()
            ->title('Test Email')
            ->info()
            ->body('Fonctionnalité de test à venir...')
            ->send();
    }

    protected function getFieldType($key): string
    {
        if (in_array($key, ['email_enabled', 'sms_enabled', 'whatsapp_enabled', 'system_notifications_enabled'])) {
            return 'boolean';
        }

        if (in_array($key, ['smtp_port', 'notification_retention_days'])) {
            return 'number';
        }

        return 'text';
    }

    protected function getFieldGroup($key): string
    {
        if (str_starts_with($key, 'smtp_') || str_starts_with($key, 'email_')) {
            return 'email';
        }

        if (str_starts_with($key, 'sms_')) {
            return 'sms';
        }

        if (str_starts_with($key, 'whatsapp_')) {
            return 'whatsapp';
        }

        return 'notifications';
    }
}
