<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return 'Template de Notification';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Templates de Notifications';
    }

    public static function getNavigationGroup(): ?string
    {
        return '⚙️ Paramétrage';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de Base')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('leave_approved, payroll_ready, etc.')
                            ->helperText('Identifiant unique du template (snake_case)'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Congé Approuvé'),

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'leave' => 'Congés',
                                'payroll' => 'Paie',
                                'procurement' => 'Marchés Publics',
                                'advancement' => 'Avancements',
                                'system' => 'Système',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Canaux Actifs')
                    ->schema([
                        Forms\Components\Toggle::make('email_enabled')
                            ->label('Email')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('sms_enabled')
                            ->label('SMS')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('whatsapp_enabled')
                            ->label('WhatsApp')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('system_enabled')
                            ->label('Notification Interne')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(4),

                Forms\Components\Tabs::make('Contenu')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Email')
                            ->schema([
                                Forms\Components\TextInput::make('email_subject')
                                    ->label('Sujet de l\'Email')
                                    ->maxLength(255)
                                    ->placeholder('Ex: Votre congé a été approuvé'),

                                Forms\Components\Textarea::make('email_body')
                                    ->label('Corps de l\'Email')
                                    ->rows(8)
                                    ->maxLength(65535)
                                    ->placeholder('Utilisez {{variable}} pour insérer des variables')
                                    ->helperText('HTML supporté'),
                            ]),

                        Forms\Components\Tabs\Tab::make('SMS')
                            ->schema([
                                Forms\Components\Textarea::make('sms_body')
                                    ->label('Message SMS')
                                    ->rows(4)
                                    ->maxLength(160)
                                    ->placeholder('Message court (max 160 caractères)')
                                    ->helperText('Utilisez {{variable}} pour insérer des variables'),
                            ]),

                        Forms\Components\Tabs\Tab::make('WhatsApp')
                            ->schema([
                                Forms\Components\Textarea::make('whatsapp_body')
                                    ->label('Message WhatsApp')
                                    ->rows(6)
                                    ->maxLength(1000)
                                    ->placeholder('Message WhatsApp')
                                    ->helperText('Utilisez {{variable}} pour insérer des variables'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Notification Interne')
                            ->schema([
                                Forms\Components\TextInput::make('system_title')
                                    ->label('Titre')
                                    ->maxLength(255)
                                    ->placeholder('Ex: Congé Approuvé'),

                                Forms\Components\Textarea::make('system_body')
                                    ->label('Message')
                                    ->rows(4)
                                    ->maxLength(65535)
                                    ->placeholder('Utilisez {{variable}} pour insérer des variables'),

                                Forms\Components\TextInput::make('system_icon')
                                    ->label('Icône Heroicon')
                                    ->maxLength(255)
                                    ->placeholder('heroicon-o-check-circle')
                                    ->helperText('Nom de l\'icône Heroicon (ex: heroicon-o-check-circle)'),

                                Forms\Components\Select::make('system_color')
                                    ->label('Couleur')
                                    ->options([
                                        'success' => 'Succès (Vert)',
                                        'info' => 'Info (Bleu)',
                                        'warning' => 'Avertissement (Orange)',
                                        'danger' => 'Danger (Rouge)',
                                    ])
                                    ->default('info')
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Variables Disponibles')
                    ->schema([
                        Forms\Components\TagsInput::make('available_variables')
                            ->label('Variables')
                            ->placeholder('Ajoutez les variables disponibles')
                            ->helperText('Ex: employee_name, leave_type, start_date, end_date')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Template Actif')
                            ->default(true)
                            ->helperText('Désactiver si ce template ne doit plus être utilisé'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->colors([
                        'primary' => 'leave',
                        'success' => 'payroll',
                        'info' => 'procurement',
                        'warning' => 'advancement',
                        'secondary' => 'system',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'leave' => 'Congés',
                        'payroll' => 'Paie',
                        'procurement' => 'Marchés Publics',
                        'advancement' => 'Avancements',
                        'system' => 'Système',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('email_enabled')
                    ->label('Email')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('sms_enabled')
                    ->label('SMS')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('whatsapp_enabled')
                    ->label('WhatsApp')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('system_enabled')
                    ->label('Interne')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'leave' => 'Congés',
                        'payroll' => 'Paie',
                        'procurement' => 'Marchés Publics',
                        'advancement' => 'Avancements',
                        'system' => 'Système',
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
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
