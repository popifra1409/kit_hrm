<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeCardResource\Pages;
use App\Models\EmployeeCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EmployeeCardResource extends Resource
{
    protected static ?string $model = EmployeeCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getModelLabel(): string
    {
        return 'Carte';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cartes Professionnelles & Santé';
    }

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employé et Type')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'matricule')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name . ' (' . $record->matricule . ')'),

                        Forms\Components\Select::make('card_type')
                            ->label('Type de Carte')
                            ->options([
                                'professional' => '🏢 Carte Professionnelle',
                                'health_coverage' => '🏥 Carte de Prise en Charge',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informations Carte')
                    ->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->label('Numéro de Carte')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Généré automatiquement'),

                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Date d\'Émission')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Date d\'Expiration')
                            ->required()
                            ->default(now()->addYear())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('issue_date'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'issued' => 'Émise',
                                'active' => 'Active',
                                'suspended' => 'Suspendue',
                                'expired' => 'Expirée',
                                'revoked' => 'Révoquée',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('QR Code')
                    ->schema([
                        Forms\Components\Placeholder::make('qr_preview')
                            ->label('QR Code')
                            ->content(function ($record) {
                                if ($record && $record->qr_code_path) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<img src="' . Storage::url($record->qr_code_path) . '" 
                                              alt="QR Code" 
                                              class="w-48 h-48 border border-gray-300 rounded">'
                                    );
                                }
                                return 'QR Code sera généré après création';
                            }),

                        Forms\Components\Textarea::make('qr_code_data')
                            ->label('Données QR Code')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(1)
                    ->visible(fn($context) => $context === 'edit')
                    ->collapsible(),

                Forms\Components\Section::make('Activation/Révocation')
                    ->schema([
                        Forms\Components\Placeholder::make('activated_info')
                            ->label('Activée par')
                            ->content(fn($record) => $record && $record->activatedBy
                                ? $record->activatedBy->name . ' le ' . $record->activated_at->format('d/m/Y à H:i')
                                : 'Non activée'),

                        Forms\Components\Placeholder::make('revoked_info')
                            ->label('Révoquée par')
                            ->content(fn($record) => $record && $record->revokedBy
                                ? $record->revokedBy->name . ' le ' . $record->revoked_at->format('d/m/Y à H:i')
                                : 'Non révoquée'),

                        Forms\Components\Textarea::make('revocation_reason')
                            ->label('Raison de Révocation')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(1)
                    ->visible(fn($context) => $context === 'edit')
                    ->collapsible(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observations')
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
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('card_type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->getCardTypeLabel())
                    ->colors([
                        'info' => 'professional',
                        'success' => 'health_coverage',
                    ])
                    ->icons([
                        'heroicon-o-briefcase' => 'professional',
                        'heroicon-o-heart' => 'health_coverage',
                    ]),

                Tables\Columns\TextColumn::make('card_number')
                    ->label('N° Carte')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Numéro copié'),

                Tables\Columns\ImageColumn::make('qr_code_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->width(50)
                    ->height(50),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Émission')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->expiry_date < now() ? 'danger' : null),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($record) => $record->getStatusLabel())
                    ->colors([
                        'warning' => fn($state) => in_array($state, ['pending', 'suspended']),
                        'info' => 'issued',
                        'success' => 'active',
                        'danger' => fn($state) => in_array($state, ['expired', 'revoked']),
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('card_type')
                    ->label('Type')
                    ->options([
                        'professional' => 'Carte Professionnelle',
                        'health_coverage' => 'Carte de Prise en Charge',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'issued' => 'Émise',
                        'active' => 'Active',
                        'suspended' => 'Suspendue',
                        'expired' => 'Expirée',
                        'revoked' => 'Révoquée',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('generate_qr')
                    ->label('Générer QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->visible(fn($record) => !$record->qr_code_path)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if (!$record->card_number) {
                            $record->generateCardNumber();
                        }
                        $record->generateQrCode();

                        \Filament\Notifications\Notification::make()
                            ->title('QR Code généré')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => !$record->is_active && $record->qr_code_path)
                    ->requiresConfirmation()
                    ->modalHeading('Activer la carte')
                    ->modalDescription('Confirmer l\'activation de cette carte ?')
                    ->action(function ($record) {
                        $record->activate();

                        \Filament\Notifications\Notification::make()
                            ->title('Carte activée')
                            ->success()
                            ->body('La carte ' . $record->card_number . ' est maintenant active')
                            ->send();
                    }),

                Tables\Actions\Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('revocation_reason')
                            ->label('Raison de la révocation')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->revoke($data['revocation_reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Carte révoquée')
                            ->warning()
                            ->body('La carte a été révoquée')
                            ->send();
                    }),

                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->visible(fn($record) => $record->card_pdf_path)
                    ->url(fn($record) => Storage::url($record->card_pdf_path))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeCards::route('/'),
            'create' => Pages\CreateEmployeeCard::route('/create'),
            'edit' => Pages\EditEmployeeCard::route('/{record}/edit'),
        ];
    }
}
