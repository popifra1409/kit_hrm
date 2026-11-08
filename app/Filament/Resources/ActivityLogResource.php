<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\Concerns\HasActivityLogTable;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Le journal d'activité : qui a fait quoi, depuis où, et ce qui a échoué.
 *
 * En lecture seule par construction — pas de page de création, d'édition ni de
 * suppression. Une ligne s'efface par le versement aux archives, jamais par la main
 * d'un agent.
 */
class ActivityLogResource extends Resource
{
    use HasActivityLogTable;

    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Journal d\'activité';
    protected static ?string $navigationGroup = '🔧 Administration';
    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Ligne du journal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Journal d\'activité';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_activity_logs') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return static::journalTable($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
