<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getModelLabel(): string
    {
        return 'Bulletin de Paie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bulletins de Paie';
    }

    public static function getNavigationGroup(): ?string
    {
        return '💰 Gestion de la Paie';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Bulletins de Paie';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations Employé')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(function () {
                                return \App\Models\Employee::where('is_active', true)
                                    ->get()
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = \App\Models\Employee::find($state);
                                    if ($employee) {
                                        $baseSalary = $employee->getBaseSalaryFromGrid();
                                        $set('base_salary', $baseSalary);
                                    }
                                }
                            }),

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
                            ->native(false)
                            ->default(now()->month),

                        Forms\Components\Select::make('year')
                            ->label('Année')
                            ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                            ->required()
                            ->native(false)
                            ->default(now()->year),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de Paiement')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->nullable(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Salaires')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->label('Salaire de Base')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('gross_taxable')
                            ->label('Salaire Imposable')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('gross_cnps')
                            ->label('Salaire Cotisable')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('gross_salary')
                            ->label('Salaire Brut')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('net_salary')
                            ->label('Net à Payer')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled()
                            ->extraAttributes(['class' => 'font-bold text-lg']),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Cotisations et Impôts')
                    ->schema([
                        Forms\Components\TextInput::make('cnps_employee')
                            ->label('CNPS Employé (4.2%)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('cnps_employer')
                            ->label('CNPS Employeur (11.2%)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('irpp')
                            ->label('IRPP')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),

                        Forms\Components\TextInput::make('cac')
                            ->label('CAC (10% IRPP)')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled(),
                    ])
                    ->columns(4)
                    ->collapsed(),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'validated' => 'Validé',
                                'paid' => 'Payé',
                                'cancelled' => 'Annulé',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Mois')
                    ->formatStateUsing(fn($state) => [
                        1 => 'Jan',
                        2 => 'Fév',
                        3 => 'Mar',
                        4 => 'Avr',
                        5 => 'Mai',
                        6 => 'Juin',
                        7 => 'Juil',
                        8 => 'Août',
                        9 => 'Sep',
                        10 => 'Oct',
                        11 => 'Nov',
                        12 => 'Déc'
                    ][$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Année')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Salaire Base')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('Brut')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_salary')
                    ->label('Net à Payer')
                    ->money('XAF')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'validated',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'validated' => 'Validé',
                        'paid' => 'Payé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date Paiement')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('month')
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
                    ]),

                Tables\Filters\SelectFilter::make('year')
                    ->label('Année')
                    ->options(array_combine(range(2020, 2030), range(2020, 2030))),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'validated' => 'Validé',
                        'paid' => 'Payé',
                        'cancelled' => 'Annulé',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),

                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'validated',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Bulletin validé')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn($record) => route('payroll.pdf', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
