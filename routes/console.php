<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('census:close-expired')->everyFifteenMinutes();

// Le journal d'activité ne garde que 12 mois en ligne chaude : le reste est versé
// dans activity_logs_archive le 1er de chaque mois à 03:00 (heure creuse).
Schedule::command('activity-logs:archive')->monthlyOn(1, '03:00');
