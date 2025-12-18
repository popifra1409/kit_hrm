<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getModelLabel(): string
    {
        return 'Paramètre Système';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Paramètres Système';
    }

    public static function getNavigationGroup(): ?string
    {
        return '⚙️ Paramétrage';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de Base')
                    ->schema([
                        Forms\Components\Select::make('group')
                            ->label('Groupe')
                            ->options([
                                'general' => 'Général',
                                'hospital' => 'Hôpital',
                                'notifications' => 'Notifications',
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp',
                                'documents' => 'Documents',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('key')
                            ->label('Clé')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ex: hospital_name, smtp_host')
                            ->helperText('Identifiant unique du paramètre (snake_case)'),

                        Forms\Components\Select::make('type')
                            ->label('Type de Valeur')
                            ->options([
                                'text' => 'Texte',
                                'number' => 'Nombre',
                                'boolean' => 'Booléen (Oui/Non)',
                                'json' => 'JSON',
                                'file' => 'Fichier',
                            ])
                            ->default('text')
                            ->required()
                            ->reactive()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Valeur')
                    ->schema([
                        Forms\Components\Textarea::make('value')
                            ->label('Valeur')
                            ->rows(3)
                            ->maxLength(65535)
                            ->visible(fn($get) => in_array($get('type'), ['text', 'json']))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('value')
                            ->label('Valeur')
                            ->numeric()
                            ->visible(fn($get) => $get('type') === 'number'),

                        Forms\Components\Toggle::make('value')
                            ->label('Valeur')
                            ->visible(fn($get) => $get('type') === 'boolean'),

                        Forms\Components\FileUpload::make('value')
                            ->label('Fichier')
                            ->directory('settings')
                            ->visible(fn($get) => $get('type') === 'file'),
                    ]),

                Forms\Components\Section::make('Description et Visibilité')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(65535)
                            ->helperText('Description du paramètre pour les administrateurs')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public')
                            ->helperText('Accessible sans authentification (ex: nom hôpital)')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Groupe')
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'success' => 'hospital',
                        'info' => 'notifications',
                        'warning' => 'email',
                        'danger' => 'sms',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'general' => 'Général',
                        'hospital' => 'Hôpital',
                        'notifications' => 'Notifications',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'documents' => 'Documents',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'secondary' => 'text',
                        'info' => 'number',
                        'success' => 'boolean',
                        'warning' => 'json',
                        'danger' => 'file',
                    ]),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Groupe')
                    ->options([
                        'general' => 'Général',
                        'hospital' => 'Hôpital',
                        'notifications' => 'Notifications',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'whatsapp' => 'WhatsApp',
                        'documents' => 'Documents',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'text' => 'Texte',
                        'number' => 'Nombre',
                        'boolean' => 'Booléen',
                        'json' => 'JSON',
                        'file' => 'Fichier',
                    ]),
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
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
}
