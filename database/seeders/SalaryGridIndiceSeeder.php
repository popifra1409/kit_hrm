<?php

namespace Database\Seeders;

use App\Models\SalaryGrid;
use App\Support\CameroonCivilServiceGrid;
use Illuminate\Database\Seeder;

/**
 * Import de la grille indiciaire officielle des fonctionnaires (02/02/2024)
 * et calcul des indices des contractuels déjà présents en base.
 *
 * Idempotent : peut être relancé sans créer de doublons.
 *   php artisan db:seed --class=SalaryGridIndiceSeeder
 */
class SalaryGridIndiceSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        foreach (CameroonCivilServiceGrid::rows() as $row) {
            $grid = SalaryGrid::firstOrNew([
                'classification_type' => 'cameroon',
                'category' => $row['category'],
                'echelon' => $row['echelon'],
            ]);

            $isNew = !$grid->exists;

            $grid->fill([
                'indice' => $row['indice'],
                'salaire_indiciaire_brut' => $row['salaire_indiciaire_brut'],
                'complement_forfaitaire' => $row['complement_forfaitaire'],
                'indemnite_logement' => $row['indemnite_logement'],
                'base_salary' => $row['salaire_indiciaire_brut'],
                'effective_date' => CameroonCivilServiceGrid::EFFECTIVE_DATE,
                'is_active' => true,
                'notes' => 'Grille officielle des fonctionnaires — en vigueur au 02/02/2024 (+5 %)',
            ]);

            $grid->save();

            $isNew ? $created++ : $updated++;
        }

        $numeric = 0;

        SalaryGrid::where('classification_type', 'numeric')->get()->each(function (SalaryGrid $grid) use (&$numeric) {
            if ($grid->base_salary !== null) {
                $grid->indice = CameroonCivilServiceGrid::nearestIndiceForSalary((float) $grid->base_salary);
                $grid->saveQuietly(); // évite de redéclencher booted::saving inutilement
                $numeric++;
            }
        });

        $legacy = SalaryGrid::where('classification_type', 'cameroon')->whereNull('indice')->count();

        $this->command?->info("✅ Fonctionnaires : {$created} ligne(s) créée(s), {$updated} mise(s) à jour.");
        $this->command?->info("✅ Contractuels : indice calculé pour {$numeric} ligne(s).");

        if ($legacy > 0) {
            $this->command?->warn("⚠️  {$legacy} ancienne(s) ligne(s) 'cameroon' hors grille officielle (sans indice) — à vérifier/désactiver manuellement.");
        }
    }
}
