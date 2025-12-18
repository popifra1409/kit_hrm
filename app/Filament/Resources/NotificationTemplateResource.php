<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Filament\Resources\NotificationTemplateResource\RelationManagers;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('email_enabled')
                    ->required(),
                Forms\Components\Toggle::make('sms_enabled')
                    ->required(),
                Forms\Components\Toggle::make('whatsapp_enabled')
                    ->required(),
                Forms\Components\Toggle::make('system_enabled')
                    ->required(),
                Forms\Components\TextInput::make('email_subject')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('email_body')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('sms_body')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('whatsapp_body')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('system_title')
                    ->maxLength(255),
                Forms\Components\Textarea::make('system_body')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('system_icon')
                    ->maxLength(255),
                Forms\Components\TextInput::make('system_color')
                    ->required()
                    ->maxLength(255)
                    ->default('info'),
                Forms\Components\TextInput::make('available_variables'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_enabled')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sms_enabled')
                    ->boolean(),
                Tables\Columns\IconColumn::make('whatsapp_enabled')
                    ->boolean(),
                Tables\Columns\IconColumn::make('system_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('email_subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('system_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('system_icon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('system_color')
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
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
