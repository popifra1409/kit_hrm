<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveApprovalStepResource\Pages;
use App\Models\LeaveApprovalStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveApprovalStepResource extends Resource
{
    protected static ?string $model = LeaveApprovalStep::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function getModelLabel(): string
    {
        return 'Étape du Circuit';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Circuit de Validation des Congés';
    }

    public static function getNavigationGroup(): ?string
    {
        return '⚙️ Paramétrage';
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Étape')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code (identifiant technique)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Ex: chef_immediat, dept_head, nursing_chief...'),

                        Forms\Components\TextInput::make('name')
                            ->label('Libellé affiché')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('order')
                            ->label('Ordre dans le circuit')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('1 = première étape après soumission de la demande'),

                        Forms\Components\Select::make('resolver_type')
                            ->label('Comment déterminer qui approuve cette étape')
                            ->options([
                                'service_head' => 'Chef du service actuel de l\'employé (Major/Chef de Service)',
                                'department_head' => 'Chef de département/sous-direction de l\'employé',
                                'role' => 'N\'importe quel utilisateur ayant un rôle donné',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive(),

                        Forms\Components\TextInput::make('resolver_role')
                            ->label('Rôle (slug Spatie)')
                            ->maxLength(255)
                            ->placeholder('Ex: chef_service_nursing, dat, daaf, dmr_dmra')
                            ->visible(fn(Forms\Get $get) => $get('resolver_type') === 'role')
                            ->required(fn(Forms\Get $get) => $get('resolver_type') === 'role'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Étape active')
                            ->default(true)
                            ->helperText('Une étape désactivée est ignorée dans le circuit sans être supprimée'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Libellé')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('resolver_type')
                    ->label('Résolution')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'service_head' => 'Chef de service',
                        'department_head' => 'Chef de département',
                        'role' => 'Par rôle',
                        default => $state,
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('resolver_role')
                    ->label('Rôle')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveApprovalSteps::route('/'),
            'create' => Pages\CreateLeaveApprovalStep::route('/create'),
            'edit' => Pages\EditLeaveApprovalStep::route('/{record}/edit'),
        ];
    }
}
