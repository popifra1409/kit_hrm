<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcurementResource\Pages;
use App\Models\Procurement;
use App\Models\ProcurementType;
use App\Models\Department;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcurementResource extends Resource
{
    protected static ?string $model = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return 'Marché Public';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Marchés Publics';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🏗️ Marchés Publics';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informations Générales')
                        ->schema([
                            Forms\Components\TextInput::make('reference')
                                ->label('Référence')
                                ->default(fn() => Procurement::generateReference())
                                ->disabled()
                                ->dehydrated()
                                ->required(),

                            Forms\Components\TextInput::make('title')
                                ->label('Objet du Marché')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('procurement_type_id')
                                ->label('Type de Marché')
                                ->options(ProcurementType::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->native(false)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $type = ProcurementType::find($state);
                                    if ($type) {
                                        $set('requires_armp', $type->requires_armp_approval);
                                    }
                                }),

                            Forms\Components\Select::make('procedure')
                                ->label('Procédure')
                                ->options([
                                    'open_tender' => 'Appel d\'Offres Ouvert',
                                    'restricted_tender' => 'Appel d\'Offres Restreint',
                                    'consultation' => 'Consultation',
                                    'direct_agreement' => 'Gré à Gré',
                                    'request_for_quote' => 'Demande de Cotation',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\Textarea::make('description')
                                ->label('Description Détaillée')
                                ->rows(4)
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Montants')
                        ->schema([
                            Forms\Components\TextInput::make('estimated_amount')
                                ->label('Montant Estimé')
                                ->required()
                                ->numeric()
                                ->prefix('FCFA')
                                ->helperText('Estimation du coût du marché'),

                            Forms\Components\TextInput::make('reserve_price')
                                ->label('Prix de Réserve')
                                ->numeric()
                                ->prefix('FCFA')
                                ->helperText('Montant maximum acceptable (optionnel)'),

                            Forms\Components\Select::make('currency')
                                ->label('Devise')
                                ->options([
                                    'FCFA' => 'FCFA',
                                    'EUR' => 'Euro',
                                    'USD' => 'Dollar US',
                                ])
                                ->default('FCFA')
                                ->native(false),
                        ])
                        ->columns(3),

                    Forms\Components\Wizard\Step::make('Service Demandeur')
                        ->schema([
                            Forms\Components\Select::make('requesting_department_id')
                                ->label('Département Demandeur')
                                ->options(Department::pluck('name', 'id'))
                                ->reactive()
                                ->native(false)
                                ->afterStateUpdated(fn(callable $set) => $set('requesting_service_id', null)),

                            Forms\Components\Select::make('requesting_service_id')
                                ->label('Service Demandeur')
                                ->options(function (callable $get) {
                                    $deptId = $get('requesting_department_id');
                                    if (!$deptId) {
                                        return Service::pluck('name', 'id');
                                    }
                                    return Service::where('department_id', $deptId)->pluck('name', 'id');
                                })
                                ->native(false),

                            Forms\Components\Hidden::make('initiated_by')
                                ->default(auth()->id()),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Calendrier')
                        ->schema([
                            Forms\Components\DatePicker::make('publication_date')
                                ->label('Date de Publication')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),

                            Forms\Components\DatePicker::make('deadline_questions')
                                ->label('Date Limite Questions')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Date limite pour les demandes d\'éclaircissement'),

                            Forms\Components\DatePicker::make('deadline_submission')
                                ->label('Date Limite Dépôt des Offres')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->helperText('Date et heure limite pour soumettre les offres'),

                            Forms\Components\DatePicker::make('opening_date')
                                ->label('Date Ouverture des Plis')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Date de la séance publique d\'ouverture'),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('ARMP')
                        ->schema([
                            Forms\Components\Toggle::make('requires_armp')
                                ->label('Nécessite Approbation ARMP')
                                ->reactive(),

                            Forms\Components\TextInput::make('armp_reference')
                                ->label('Référence ARMP')
                                ->maxLength(255)
                                ->visible(fn(callable $get) => $get('requires_armp')),

                            Forms\Components\DatePicker::make('armp_submission_date')
                                ->label('Date de Soumission ARMP')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->visible(fn(callable $get) => $get('requires_armp')),

                            Forms\Components\Select::make('armp_status')
                                ->label('Statut ARMP')
                                ->options([
                                    'not_required' => 'Non Requis',
                                    'pending' => 'En Attente',
                                    'approved' => 'Approuvé',
                                    'rejected' => 'Rejeté',
                                ])
                                ->default('not_required')
                                ->native(false)
                                ->visible(fn(callable $get) => $get('requires_armp')),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Notes')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes et Observations')
                                ->rows(5)
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Objet')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->title),

                Tables\Columns\TextColumn::make('procurementType.name')
                    ->label('Type')
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('procedure')
                    ->label('Procédure')
                    ->colors([
                        'primary' => 'open_tender',
                        'success' => 'restricted_tender',
                        'warning' => 'consultation',
                        'danger' => 'direct_agreement',
                        'secondary' => 'request_for_quote',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'open_tender' => 'AO Ouvert',
                        'restricted_tender' => 'AO Restreint',
                        'consultation' => 'Consultation',
                        'direct_agreement' => 'Gré à Gré',
                        'request_for_quote' => 'Demande Cotation',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('estimated_amount')
                    ->label('Montant Estimé')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('deadline_submission')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_approval',
                        'info' => 'approved',
                        'primary' => 'published',
                        'success' => fn($state) => in_array($state, ['bids_received', 'evaluation', 'awarded', 'contract_signed']),
                        'danger' => fn($state) => in_array($state, ['cancelled', 'rejected']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'pending_approval' => 'En Approbation',
                        'approved' => 'Approuvé',
                        'published' => 'Publié',
                        'bids_received' => 'Offres Reçues',
                        'evaluation' => 'En Évaluation',
                        'awarded' => 'Attribué',
                        'contract_signed' => 'Contrat Signé',
                        'cancelled' => 'Annulé',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('procurement_type_id')
                    ->label('Type de Marché')
                    ->options(ProcurementType::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('procedure')
                    ->label('Procédure')
                    ->options([
                        'open_tender' => 'AO Ouvert',
                        'restricted_tender' => 'AO Restreint',
                        'consultation' => 'Consultation',
                        'direct_agreement' => 'Gré à Gré',
                        'request_for_quote' => 'Demande Cotation',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'pending_approval' => 'En Approbation',
                        'approved' => 'Approuvé',
                        'published' => 'Publié',
                        'bids_received' => 'Offres Reçues',
                        'evaluation' => 'En Évaluation',
                        'awarded' => 'Attribué',
                        'contract_signed' => 'Contrat Signé',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('requires_armp')
                    ->label('Nécessite ARMP')
                    ->query(fn(Builder $query): Builder => $query->where('requires_armp', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('publish')
                    ->label('Publier')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'published']);
                        \Filament\Notifications\Notification::make()
                            ->title('Marché publié')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('award')
                    ->label('Attribuer')
                    ->icon('heroicon-o-trophy')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'evaluation')
                    ->url(fn($record) => route('filament.admin.resources.procurements.award', $record)),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcurements::route('/'),
            'create' => Pages\CreateProcurement::route('/create'),
            'view' => Pages\ViewProcurement::route('/{record}'),
            'edit' => Pages\EditProcurement::route('/{record}/edit'),
        ];
    }
}
