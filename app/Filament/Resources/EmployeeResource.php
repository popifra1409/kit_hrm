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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('matricule')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category_recruitment')
                    ->maxLength(255),
                Forms\Components\TextInput::make('category_current')
                    ->maxLength(255),
                Forms\Components\TextInput::make('category_number')
                    ->numeric(),
                Forms\Components\TextInput::make('echelon_number')
                    ->numeric(),
                Forms\Components\TextInput::make('qualification')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('department_id')
                    ->numeric(),
                Forms\Components\TextInput::make('position_id')
                    ->numeric(),
                Forms\Components\TextInput::make('employment_type')
                    ->required()
                    ->maxLength(255)
                    ->default('permanent'),
                Forms\Components\TextInput::make('personnel_type')
                    ->required()
                    ->maxLength(255)
                    ->default('non_soignant'),
                Forms\Components\DatePicker::make('birth_date')
                    ->required(),
                Forms\Components\DatePicker::make('recruitment_date')
                    ->required(),
                Forms\Components\DatePicker::make('service_start_date')
                    ->required(),
                Forms\Components\DatePicker::make('retirement_date'),
                Forms\Components\TextInput::make('retirement_age')
                    ->required()
                    ->numeric()
                    ->default(60),
                Forms\Components\TextInput::make('contract_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_account_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('city')
                    ->maxLength(255),
                Forms\Components\TextInput::make('marital_status')
                    ->required()
                    ->maxLength(255)
                    ->default('single'),
                Forms\Components\TextInput::make('children_under_6')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_children')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('disciplinary_points')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('disciplinary_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('active'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\TextInput::make('current_service_id')
                    ->numeric(),
                Forms\Components\TextInput::make('contract_type_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricule')
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_recruitment')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_current')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('echelon_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qualification')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employment_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('personnel_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recruitment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retirement_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retirement_age')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marital_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('children_under_6')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_children')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('disciplinary_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_service_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract_type_id')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    // Après la classe, ajoutez ces méthodes statiques

    public static function getNavigationGroup(): ?string
    {
        return '👥 Gestion du Personnel';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Employés';
    }
}
