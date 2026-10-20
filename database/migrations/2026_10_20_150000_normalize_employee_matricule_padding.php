<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Normalise les matricules historiques portant des espaces en début/fin de valeur.
 *
 * L'index unique de employees.matricule compare les valeurs brutes : « 0969 » et
 * « 0969  » y cohabitent, et un employé dont la ligne est stockée avec l'espace ne
 * peut pas être retrouvé par l'API (le client, lui, trimme sa saisie).
 *
 * Les lignes dont la valeur trimée est déjà prise par une autre ligne sont laissées
 * telles quelles : les trimmer violerait l'index unique et ferait échouer la
 * migration. Elles sont journalisées par la migration et relevables avec :
 *
 *   select e.id, e.matricule, o.id, o.last_name
 *     from employees e
 *     join employees o on o.matricule = btrim(e.matricule) and o.id <> e.id
 *    where e.matricule <> btrim(e.matricule);
 *
 * Ce sont des doublons réels à trancher par les RH.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $blockedIds = array_map(
                fn($row) => $row->id,
                DB::select(
                    'select e.id
                       from employees e
                       join employees o on o.matricule = btrim(e.matricule) and o.id <> e.id
                      where e.matricule <> btrim(e.matricule)'
                )
            );

            $pending = DB::select(
                'select id, matricule, btrim(matricule) normalized
                   from employees
                  where matricule <> btrim(matricule)'
            );

            if (!$blockedIds) {
                if (!$pending) {
                    return;
                }

                DB::statement('update employees set matricule = btrim(matricule) where matricule <> btrim(matricule)');
                $this->log(count($pending), 0, []);

                return;
            }

            $placeholders = implode(',', array_fill(0, count($blockedIds), '?'));

            $normalized = DB::select(
                "update employees
                    set matricule = btrim(matricule)
                  where matricule <> btrim(matricule)
                    and id not in ({$placeholders})
              returning id, btrim(matricule) as matricule",
                $blockedIds
            );

            $skipped = array_values(array_filter(
                $pending,
                fn($row) => in_array($row->id, $blockedIds, true)
            ));

            $this->log(count($normalized), count($skipped), $skipped);
        });
    }

    public function down(): void
    {
        // Irréversible : les espaces d'origine ne sont conservés nulle part.
    }

    private function log(int $count, int $skippedCount, array $skipped): void
    {
        Log::notice('Normalisation des matricules employees', [
            'normalized' => $count,
            'skipped_conflicts' => $skippedCount,
            'skipped_rows' => array_map(
                fn($row) => ['id' => $row->id, 'stored' => $row->matricule, 'conflicts_with' => $row->normalized],
                $skipped
            ),
        ]);
    }
};
