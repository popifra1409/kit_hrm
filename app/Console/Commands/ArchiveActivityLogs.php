<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verse les lignes anciennes du journal dans activity_logs_archive.
 *
 * Le but n'est pas de jeter : la table chaude reste petite et rapide, l'historique
 * reste consultable écran « Journaux archivés ». Une ligne ne bouge que vers l'archive,
 * et l'archive n'est jamais purgée par cette commande.
 */
class ArchiveActivityLogs extends Command
{
    /** Mois conservés dans la table chaude quand l'option --months est absente. */
    public const RETENTION_MONTHS = 12;

    /** Lignes par lot : assez grand pour être rapide, assez petit pour une transaction courte. */
    private const CHUNK = 5000;

    protected $signature = 'activity-logs:archive
                            {--months= : Nombre de mois conservés dans la table chaude (défaut : ' . self::RETENTION_MONTHS . ')}
                            {--dry-run : compte les lignes à verser sans rien déplacer}';

    protected $description = 'Verse les journaux d’activité plus anciens que la période de rétention dans la table d’archive';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: self::RETENTION_MONTHS);

        if ($months < 1) {
            $this->error('La rétention doit être d’au moins un mois.');

            return self::FAILURE;
        }

        $cut = now()->startOfMonth()->subMonths($months);

        $query = ActivityLog::query()->where('occurred_at', '<', $cut);
        $pending = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("{$pending} ligne(s) antérieure(s) au {$cut->format('d/m/Y')} à verser (aucun déplacement).");

            return self::SUCCESS;
        }

        if ($pending === 0) {
            $this->info('Rien à verser : le journal chaud est dans la période de rétention.');

            return self::SUCCESS;
        }

        $moved = 0;

        while (true) {
            // Les identifiants sont figés par lot : la requête de suppression ne peut
            // pas attraper une ligne insérée pendant le versement.
            $ids = DB::table('activity_logs')
                ->where('occurred_at', '<', $cut)
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($ids, &$moved) {
                $rows = DB::table('activity_logs')->whereIn('id', $ids)->get();

                $toInsert = $rows->map(function ($row) {
                    $payload = (array) $row;
                    unset($payload['id']);
                    $payload['period'] = \Illuminate\Support\Carbon::parse($payload['occurred_at'])->format('Y-m');
                    $payload['archived_at'] = now();

                    return $payload;
                })->all();

                DB::table('activity_logs_archive')->insert($toInsert);
                DB::table('activity_logs')->whereIn('id', $ids)->delete();

                $moved += count($toInsert);
            });

            $this->line("  lot versé : " . count($ids) . " ligne(s)");
        }

        ActivityLogger::record(
            action: 'archived',
            status: ActivityLog::STATUS_SUCCESS,
            properties: ['lignes' => $moved, 'retention_mois' => $months, 'limite' => $cut->toDateTimeString()],
            description: "Versement de {$moved} ligne(s) du journal chaud vers l'archive",
            context: ActivityLog::CONTEXT_CONSOLE,
        );

        $this->info("{$moved} ligne(s) versée(s) dans activity_logs_archive.");

        return self::SUCCESS;
    }
}
