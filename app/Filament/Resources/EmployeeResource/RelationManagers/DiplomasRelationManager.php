<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\EmployeeDiploma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;

class DiplomasRelationManager extends RelationManager
{
    protected static string $relationship = 'diplomas';

    protected static ?string $title = 'Diplômes & Formations';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        EmployeeDiploma::TYPE_RECRUITMENT => '🎓 Diplôme de Recrutement (obligatoire)',
                        EmployeeDiploma::TYPE_HIGHEST => '🏆 Diplôme le Plus Élevé',
                        EmployeeDiploma::TYPE_TRAINING => '📘 Formation',
                    ])
                    ->required()
                    ->native(false)
                    ->reactive()
                    ->helperText('Un seul diplôme de recrutement et un seul diplôme le plus élevé par employé. Les formations sont illimitées.'),

                Forms\Components\TextInput::make('title')
                    ->label('Intitulé')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Licence en Sciences Infirmières, Certification ITIL...')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('institution')
                    ->label('École / Université / Organisme')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('year_obtained')
                    ->label("Année d'obtention")
                    ->required()
                    ->numeric()
                    ->minValue(1950)
                    ->maxValue((int) now()->format('Y')),

                Forms\Components\FileUpload::make('document_path')
                    ->label('Document justificatif (attestation)')
                    ->directory('employees/diplomas')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(5120)
                    ->required(fn(Forms\Get $get) => in_array($get('type'), [
                        EmployeeDiploma::TYPE_RECRUITMENT,
                        EmployeeDiploma::TYPE_HIGHEST,
                    ]))
                    ->helperText('PDF ou image, 5 Mo max. Sert de base à la vérification par les RH.')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function canCreateForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn($record) => $record->type_label)
                    ->colors([
                        'danger' => EmployeeDiploma::TYPE_RECRUITMENT,
                        'warning' => EmployeeDiploma::TYPE_HIGHEST,
                        'info' => EmployeeDiploma::TYPE_TRAINING,
                    ]),

                Tables\Columns\TextColumn::make('title')
                    ->label('Intitulé')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('institution')
                    ->label('Établissement')
                    ->searchable(),

                Tables\Columns\TextColumn::make('year_obtained')
                    ->label('Année')
                    ->sortable(),

                Tables\Columns\IconColumn::make('document_path')
                    ->label('Document')
                    ->getStateUsing(fn($record) => (bool) $record->document_path)
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Vérifié RH')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('year_obtained', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_document')
                        ->label('Voir le document')
                        ->icon('heroicon-o-eye')
                        ->visible(fn($record) => (bool) $record->document_path)
                        ->url(fn($record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->document_path))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('verify')
                        ->label('Marquer vérifié')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn($record) => !$record->is_verified)
                        ->requiresConfirmation()
                        ->action(fn($record) => $record->markVerified()),

                    Tables\Actions\EditAction::make()->label('Modifier'),
                    Tables\Actions\DeleteAction::make()->label('Supprimer'),
                ])
                    ->button()
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
