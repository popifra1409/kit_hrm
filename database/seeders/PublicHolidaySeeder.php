<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicHoliday;

class PublicHolidaySeeder extends Seeder
{
    public function run(): void
    {
        // Jours fériés fixes (mêmes date chaque année)
        $recurring = [
            ['date' => '2026-01-01', 'name' => "Jour de l'An"],
            ['date' => '2026-02-11', 'name' => 'Fête de la Jeunesse'],
            ['date' => '2026-05-01', 'name' => 'Fête du Travail'],
            ['date' => '2026-05-20', 'name' => 'Fête Nationale'],
            ['date' => '2026-08-15', 'name' => 'Assomption'],
            ['date' => '2026-12-25', 'name' => 'Noël'],
        ];

        foreach ($recurring as $holiday) {
            PublicHoliday::firstOrCreate(
                ['date' => $holiday['date']],
                ['name' => $holiday['name'], 'is_recurring_yearly' => true]
            );
        }

        echo "✅ " . count($recurring) . " jours fériés fixes créés.\n";
        echo "⚠️  Pensez à ajouter chaque année les fêtes mobiles (Vendredi Saint, Ascension, Aïd el-Fitr, Aïd el-Kébir) via le module Jours Fériés — leurs dates changent chaque année.\n";
    }
}
