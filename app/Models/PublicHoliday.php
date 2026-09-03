<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PublicHoliday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'is_recurring_yearly',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring_yearly' => 'boolean',
    ];

    /**
     * Vérifie si une date donnée est un jour férié
     * (gère aussi les jours fériés récurrents d'une année sur l'autre).
     */
    public static function isHoliday(Carbon $date): bool
    {
        $dateStr = $date->format('Y-m-d');
        $monthDay = $date->format('m-d');

        return static::where('date', $dateStr)
            ->orWhere(function ($query) use ($monthDay) {
                $query->where('is_recurring_yearly', true)
                    ->whereRaw("to_char(date, 'MM-DD') = ?", [$monthDay]);
            })
            ->exists();
    }
}
