<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotpartPeriodResource\Pages;
use App\Models\QuotpartPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class QuotpartPeriodResource extends Resource
{
    protected static ?string $model = QuotpartPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Période de Quote-Part';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Périodes de Quote-Parts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification de la Période')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->placeholder('2026-04')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Format: YYYY-MM'),

                                Forms\Components\Select::make('year')
                                    ->label('Année')
                                    ->options(function () {
                                        $currentYear = now()->year;
                                        $years = [];
                                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                            $years[$i] = $i;
                                        }
                                        return $years;
                                    })
                                    ->required()
                                    ->default(now()->year)
                                    ->reactive()
                                    ->afterStateUpdated(
                                        fn($state, callable $set, callable $get) =>
                                        $set('code', $state . '-' . str_pad($get('month') ?? 1, 2, '0', STR_PAD_LEFT))
                                    ),

                                Forms\Components\Select::make('month')
                                    ->label('Mois')
                                    ->options([
                                        1 => 'Janvier',
                                        2 => 'Février',
                                        3 => 'Mars',
                                        4 => 'Avril',
                                        5 => 'Mai',
                                        6 => 'Juin',
                                        7 => 'Juillet',
                                        8 => 'Août',
                                        9 => 'Septembre',
                                        10 => 'Octobre',
                                        11 => 'Novembre',
                                        12 => 'Décembre'
                                    ])
                                    ->required()
                                    ->default(now()->month)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $year = $get('year');
                                        $set('code', $year . '-' . str_pad($state, 2, '0', STR_PAD_LEFT));

                                        // Calculer automatiquement les dates
                                        $startDate = now()->setYear($year)->setMonth($state)->startOfMonth();
                                        $endDate = now()->setYear($year)->setMonth($state)->endOfMonth();

                                        $set('start_date', $startDate->format('Y-m-d'));
                                        $set('end_date', $endDate->format('Y-m-d'));
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
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
                                    ->displayFormat('d/m/Y'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Recettes et Montants')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_revenue')
                                    ->label('Recette Totale (FCFA)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->placeholder('0')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $percentage = $get('quotpart_percentage') ?? 0;
                                        $amount = ($state * $percentage) / 100;
                                        $set('quotpart_amount', round($amount, 2));
                                    }),

                                Forms\Components\TextInput::make('quotpart_percentage')
                                    ->label('Pourcentage Quote-Part (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(10)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $revenue = $get('total_revenue') ?? 0;
                                        $amount = ($revenue * $state) / 100;
                                        $set('quotpart_amount', round($amount, 2));
                                    }),

                                Forms\Components\TextInput::make('quotpart_amount')
                                    ->label('Montant à Distribuer (FCFA)')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Calculé automatiquement'),
                            ]),
                    ]),

                Forms\Components\Section::make('Statut et Validation')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => '📝 Brouillon',
                                        'validated' => '✅ Validé',
                                        'calculated' => '🧮 Calculé',
                                        'distributed' => '💸 Distribué',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('validated_by')
                                    ->label('Validé par')
                                    ->relationship('validator', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(
                                        fn(callable $get) =>
                                        in_array($get('status'), ['validated', 'calculated', 'distributed'])
                                    ),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Observations, remarques...')
                            ->columnSpanFull(),
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
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Période')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Recette Totale')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quotpart_percentage')
                    ->label('% Quote-Part')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quotpart_amount')
                    ->label('Montant à Distribuer')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'draft' => '📝 Brouillon',
                        'validated' => '✅ Validé',
                        'calculated' => '🧮 Calculé',
                        'distributed' => '💸 Distribué',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'validated',
                        'info' => 'calculated',
                        'primary' => 'distributed',
                    ]),

                Tables\Columns\TextColumn::make('distributions_count')
                    ->label('Employés')
                    ->counts('distributions')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Année')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'validated' => 'Validé',
                        'calculated' => 'Calculé',
                        'distributed' => 'Distribué',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('validate')
                        ->label('Valider')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(QuotpartPeriod $record) => $record->status === 'draft')
                        ->action(function (QuotpartPeriod $record) {
                            $record->update([
                                'status' => 'validated',
                                'validated_at' => now(),
                                'validated_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Période validée')
                                ->body("La période {$record->full_name} a été validée avec succès.")
                                ->send();
                        }),

                    Tables\Actions\Action::make('calculate')
                        ->label('Calculer Quote-Parts')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn(QuotpartPeriod $record) => $record->status === 'validated')
                        ->action(function (QuotpartPeriod $record) {
                            // TODO: Appeler le service de calcul
                            Notification::make()
                                ->info()
                                ->title('Calcul en cours')
                                ->body('Le calcul des quote-parts va démarrer...')
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotpartPeriods::route('/'),
            'create' => Pages\CreateQuotpartPeriod::route('/create'),
            'edit' => Pages\EditQuotpartPeriod::route('/{record}/edit'),
            'view' => Pages\ViewQuotpartPeriod::route('/{record}'),
        ];
    }
}
