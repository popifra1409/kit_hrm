<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogArchiveResource\Pages;
use App\Filament\Resources\Concerns\HasActivityLogTable;
use App\Models\ActivityLogArchive;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Les lignes du journal versées hors de la table chaude.
 *
 * Même écran, même lecture : seule la période change. L'archive n'est jamais purgée par
 * le logiciel — sa conservation se décide avec les Archives de l'établissement.
 */
class ActivityLogArchiveResource extends Resource
{
    use HasActivityLogTable;

    protected static ?string $model = ActivityLogArchive::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Archives du journal';
    protected static ?string $navigationGroup = '🔧 Administration';
    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'Ligne archivée';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Archives du journal';
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
        return static::journalTable($table, avecArchive: true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogArchives::route('/'),
        ];
    }
}
