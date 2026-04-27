<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalActivityResource\Pages;
use App\Models\MedicalActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalActivityResource extends Resource
{
    protected static ?string $model = MedicalActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = '💰 Quote-Parts';
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Activité Médicale';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Activités Médicales';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Employé (Personnel Soignant)')
                                    ->relationship('employee', 'matricule')
                                    ->searchable()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->full_name . ' (' . $record->matricule . ')'
                                    )
                                    ->preload(),

                                Forms\Components\Select::make('period_id')
                                    ->label('Période')
                                    ->relationship('period', 'code')
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->code . ' - ' . $record->getMonthNameAttribute()
                                    )
                                    ->preload(),

                                Forms\Components\DatePicker::make('activity_date')
                                    ->label('Date de l\'Activité')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('Type d\'Activité')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('activity_type')
                                    ->label('Type')
                                    ->options([
                                        'consultation' => '🩺 Consultation',
                                        'prescription' => '💊 Prescription',
                                        'acte' => '🔬 Acte Médical',
                                        'garde' => '🌙 Garde',
                                        'astreinte' => '📞 Astreinte',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $unitValue = $get('unit_value') ?? 0;
                                        $set('total_value', $state * $unitValue);
                                    }),

                                Forms\Components\TextInput::make('unit_value')
                                    ->label('Valeur Unitaire')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantity = $get('quantity') ?? 1;
                                        $set('total_value', $quantity * $state);
                                    }),
                            ]),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Valeur Totale')
                            ->numeric()
                            ->prefix('FCFA')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),

                        Forms\Components\Textarea::make('description')
                            ->label('Description / Détails')
                            ->rows(3)
                            ->placeholder('Détails de l\'activité...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Validation')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_validated')
                                    ->label('Validée')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\Select::make('validated_by')
                                    ->label('Validée par')
                                    ->relationship('validator', 'matricule')
                                    ->searchable()
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        $record->full_name . ' (' . $record->matricule . ')'
                                    )
                                    ->preload()
                                    ->visible(fn(callable $get) => $get('is_validated')),

                                Forms\Components\DateTimePicker::make('validated_at')
                                    ->label('Date de Validation')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->visible(fn(callable $get) => $get('is_validated')),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MedicalActivity $record) => $record->employee?->matricule),

                Tables\Columns\TextColumn::make('period.code')
                    ->label('Période')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('activity_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('activity_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'consultation' => '🩺 Consultation',
                        'prescription' => '💊 Prescription',
                        'acte' => '🔬 Acte',
                        'garde' => '🌙 Garde',
                        'astreinte' => '📞 Astreinte',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'consultation',
                        'success' => 'prescription',
                        'warning' => 'acte',
                        'danger' => 'garde',
                        'info' => 'astreinte',
                    ]),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qté')
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valeur Totale')
                    ->money('XAF')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_validated')
                    ->label('Validée')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period_id')
                    ->label('Période')
                    ->relationship('period', 'code'),

                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Type d\'Activité')
                    ->options([
                        'consultation' => 'Consultation',
                        'prescription' => 'Prescription',
                        'acte' => 'Acte Médical',
                        'garde' => 'Garde',
                        'astreinte' => 'Astreinte',
                    ]),

                Tables\Filters\TernaryFilter::make('is_validated')
                    ->label('Validée'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('activity_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicalActivities::route('/'),
            'create' => Pages\CreateMedicalActivity::route('/create'),
            'edit' => Pages\EditMedicalActivity::route('/{record}/edit'),
        ];
    }
}
