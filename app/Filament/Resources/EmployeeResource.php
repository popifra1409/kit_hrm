<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Traits\HasAuthorization;

class EmployeeResource extends Resource
{
    use HasAuthorization;

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Employés';

    protected static ?string $navigationGroup = '👥 Gestion du Personnel';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Employé';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Employés';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Ajoutez votre formulaire ici
                Forms\Components\Section::make('Informations Générales')
                    ->schema([
                        Forms\Components\TextInput::make('matricule')
                            ->label('Matricule')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Prénom')
                            ->required(),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Nom')
                            ->required(),

                        // Ajoutez les autres champs...
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Matricule copié'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Sexe')
                    ->formatStateUsing(fn($state) => $state === 'M' ? '👨 Masculin' : '👩 Féminin')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ImageColumn::make('qr_code_path')
                    ->label('QR Code')
                    ->disk('public')
                    ->width(40)
                    ->height(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('qualification')
                    ->label('Qualification')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('currentService.name')
                    ->label('Service')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('personnel_type')
                    ->label('Type Personnel')
                    ->badge()
                    ->colors([
                        'success' => 'soignant',
                        'info' => 'paramedical',
                        'warning' => 'non_soignant',
                        'gray' => 'autres',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'soignant' => '👨‍⚕️ Soignant',
                        'non_soignant' => '💼 Non-Soignant',
                        'paramedical' => '🩺 Paramédical',
                        'autres' => '🛠️ Autres',
                        default => $state,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('classification')
                    ->label('Classification')
                    ->getStateUsing(
                        fn($record) =>
                        $record->category_number && $record->echelon_number && $record->indice
                            ? "Cat. {$record->category_number} / Éch. {$record->echelon_number} / Ind. {$record->indice}"
                            : ($record->category_number && $record->echelon_number
                                ? "Cat. {$record->category_number} / Éch. {$record->echelon_number}"
                                : 'Non définie')
                    )
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_leave',
                        'danger' => ['suspended', 'terminated'],
                        'secondary' => 'retired',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'retired' => 'Retraité',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('recruitment_date')
                    ->label('Date recrutement')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('retirement_date')
                    ->label('Date retraite')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('personnel_type')
                    ->label('Type de Personnel')
                    ->options([
                        'soignant' => 'Personnel Soignant',
                        'non_soignant' => 'Personnel Non-Soignant',
                        'paramedical' => 'Personnel Paramédical',
                        'autres' => 'Autres',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'on_leave' => 'En congé',
                        'retired' => 'Retraité',
                        'suspended' => 'Suspendu',
                        'terminated' => 'Résilié',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('current_service_id')
                    ->label('Service')
                    ->relationship('currentService', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make()
                    ->label('Archivés'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('generate_professional_card')
                        ->label('Carte Pro')
                        ->icon('heroicon-o-identification')
                        ->color('info')
                        ->visible(
                            fn($record) =>
                            !$record->hasActiveProfessionalCard() &&
                                static::checkCan('update', $record) // ✅ Correction
                        )
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            try {
                                // Vérifier carte existante
                                $existingCard = \App\Models\EmployeeCard::where('employee_id', $record->id)
                                    ->where('card_type', 'professional')
                                    ->where('is_active', true)
                                    ->first();

                                if ($existingCard) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Carte déjà existante')
                                        ->warning()
                                        ->body('Carte N° ' . $existingCard->card_number)
                                        ->send();
                                    return;
                                }

                                // Créer la carte
                                $card = \App\Models\EmployeeCard::create([
                                    'employee_id' => $record->id,
                                    'card_type' => 'professional',
                                    'issue_date' => now(),
                                    'expiry_date' => now()->addYears(5),
                                    'status' => 'issued',
                                ]);

                                $card->generateCardNumber();
                                $card->generateQrCode();

                                // Générer le PDF
                                $pdfService = new \App\Services\CardPdfService();
                                $pdfPath = $pdfService->generateProfessionalCard($card);

                                $card->activate();

                                \Filament\Notifications\Notification::make()
                                    ->title('Carte professionnelle créée')
                                    ->success()
                                    ->body('N° ' . $card->card_number)
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('download')
                                            ->label('Télécharger PDF')
                                            ->url(\Storage::url($pdfPath))
                                            ->openUrlInNewTab(),
                                    ])
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Erreur')
                                    ->danger()
                                    ->body($e->getMessage())
                                    ->send();

                                \Log::error('Erreur création carte', [
                                    'employee_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }),

                    Tables\Actions\Action::make('generate_health_card')
                        ->label('Carte Santé')
                        ->icon('heroicon-o-heart')
                        ->color('success')
                        ->visible(
                            fn($record) =>
                            !$record->hasActiveHealthCard() &&
                                static::checkCan('update', $record) // ✅ Correction
                        )
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $card = \App\Models\EmployeeCard::create([
                                'employee_id' => $record->id,
                                'card_type' => 'health_coverage',
                                'issue_date' => now(),
                                'expiry_date' => now()->addYear(),
                                'status' => 'issued',
                            ]);

                            $card->generateCardNumber();
                            $card->generateQrCode();
                            $card->activate();

                            \Filament\Notifications\Notification::make()
                                ->title('Carte de prise en charge créée')
                                ->success()
                                ->body('N° ' . $card->card_number)
                                ->send();
                        }),

                    Tables\Actions\ViewAction::make()
                        ->label('Voir')
                        ->visible(fn($record) => static::checkCan('view', $record)), // ✅ Correction

                    Tables\Actions\EditAction::make()
                        ->label('Modifier')
                        ->visible(fn($record) => static::checkCan('update', $record)), // ✅ Correction

                    Tables\Actions\DeleteAction::make()
                        ->label('Supprimer')
                        ->visible(fn($record) => static::checkCan('delete', $record)), // ✅ Correction
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélection')
                        ->visible(fn() => static::canDeleteAny()), // ✅ Correction - Pas de $record
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AssignmentHistoryRelationManager::class,
            RelationManagers\AdvancementHistoryRelationManager::class,
            RelationManagers\DependentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
