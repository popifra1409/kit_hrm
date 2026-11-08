<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ecrit une ligne dans le journal d'activité.
 *
 * Trois règles commandent tout le reste :
 *  - ça ne doit jamais faire échouer l'action en cours : une erreur de journal se
 *    constate dans les logs, pas à l'écran de l'agent ;
 *  - ça ne doit jamais écrire un secret : mot de passe, token, RIB et pièces
 *    téléversées sont écartés quel que soit ce que passe l'appelant ;
 *  - ça doit rester lisible après coup : les noms et matricules sont recopiés dans la
 *    ligne, pas seulement reliés par une clé étrangère.
 */
class ActivityLogger
{
    /**
     * Ce qui ne doit jamais atterrir dans `properties`, même par accident. Les coordonnées
     * bancaires, d'état civil et les chemins de pièces téléversées sont du même ordre : le
     * journal note qu'elles ont changé, pas leur valeur — la valeur reste dans la fiche.
     */
    private const REDACTED_PATTERN = '/password|passwd|token|secret|signature|rib|pin|cv|photo|file|fichier|document|attachment|image|bank|banque|cnps|id_card|passport|passeport|_path/i';

    /** Bornes : le journal ne doit pas grossir à cause d'un gros payload. */
    private const MAX_PROPERTIES = 40;
    private const MAX_VALUE = 200;
    private const MAX_TEXT = 1000;

    /**
     * @param  string       $action   verbe du catalogue ActivityLog::ACTIONS
     * @param  string       $status   success | failed | blocked
     * @param  Model|null   $subject  le dossier concerné (employé, contrat, demande…)
     * @param  string|null  $reason   en clair, le motif d'un refus ou d'un échec
     * @param  array        $properties  ce qui a changé, ce qui a été saisi — assaini
     * @param  Model|null   $actor    le compte qui agit ; le connecté par défaut
     * @param  string|null  $actorMatricule  le matricule tenté, quand il n'y a pas de compte
     */
    public static function record(
        string $action,
        string $status = ActivityLog::STATUS_SUCCESS,
        ?Model $subject = null,
        ?string $reason = null,
        array $properties = [],
        ?string $description = null,
        ?Model $actor = null,
        ?string $context = null,
        ?string $actorMatricule = null,
    ): void {
        $actor ??= auth()->user();

        if ($actor !== null && $actorMatricule === null) {
            $actorMatricule = $actor->employee?->matricule;
        }

        $row = [
            'occurred_at' => now(),
            'actor_user_id' => $actor?->id,
            'actor_matricule' => $actorMatricule === null ? null : self::cut($actorMatricule, 100),
            'actor_name' => $actor === null ? null : self::cut(self::personName($actor), 150),
            'context' => $context ?? self::context(),
            'action' => self::cut($action, 60),
            'status' => $status,
            'subject_type' => $subject === null ? null : self::cut($subject::class, 255),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject === null ? null : self::subjectLabel($subject),
            'reason' => $reason === null ? null : self::cut($reason, self::MAX_TEXT),
            'description' => $description === null ? null : self::cut($description, self::MAX_TEXT),
            'properties' => $properties === [] ? null : json_encode(self::sanitize($properties), JSON_UNESCAPED_UNICODE),
            'ip_address' => self::ip(),
            'user_agent' => self::userAgent(),
        ];

        try {
            // insert() et non create() : aucun événement modèle ne repasse par ici,
            // et une table absente (déploiement avant la migration) ne casse rien.
            DB::table('activity_logs')->insert($row);
        } catch (QueryException $e) {
            \Log::warning("Journal d'activité : table indisponible ({$e->getMessage()})");
        } catch (Throwable $e) {
            \Log::warning("Journal d'activité : écriture refusée pour « {$action} » ({$e->getMessage()})");
        }
    }

    /** Raccourci pour une tentative sans compte validé : le matricule tapé reste la trace. */
    public static function recordAttempt(string $action, ?string $matricule, string $reason, array $properties = [], string $status = ActivityLog::STATUS_FAILED): void
    {
        self::record(
            action: $action,
            status: $status,
            reason: $reason,
            properties: $properties,
            actorMatricule: $matricule,
            context: ActivityLog::CONTEXT_API,
        );
    }

    /** Nom lisible d'un compte : celui de l'employé lié, sinon celui de l'utilisateur. */
    private static function personName(Model $actor): string
    {
        $employee = $actor->employee ?? null;

        if ($employee !== null) {
            return trim("{$employee->last_name} {$employee->first_name}");
        }

        return (string) ($actor->name ?? '');
    }

    /**
     * Désignation du sujet, recopiée dans la ligne : c'est ce que l'agent lit dans la
     * colonne « Dossier », y compris quand le dossier a été supprimé depuis.
     */
    private static function subjectLabel(Model $subject): string
    {
        foreach (['matricule', 'code', 'reference', 'number', 'name', 'label', 'title'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return self::cut(trim($value), 150);
            }
        }

        return self::cut(class_basename($subject) . ' #' . $subject->getKey(), 150);
    }

    /**
     * Ce que l'on veut dire, pas ce que l'on a sous la main : les secrets sautent, les
     * objets deviennent leur type, les valeurs longues sont tronquées.
     */
    private static function sanitize(array $properties, int $depth = 0): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if (count($clean) >= self::MAX_PROPERTIES) {
                $clean['…'] = 'liste tronquée';
                break;
            }

            if (preg_match(self::REDACTED_PATTERN, (string) $key) === 1) {
                $clean[$key] = '[non consigné]';
                continue;
            }

            $clean[$key] = self::value($value, $depth);
        }

        return $clean;
    }

    private static function value(mixed $value, int $depth = 0): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return self::cut($value, self::MAX_VALUE);
        }

        if ($value instanceof Model) {
            return self::subjectLabel($value);
        }

        if (is_array($value)) {
            // Un diff « avant / après » est un tableau de tableaux : la purge des clés
            // sensibles doit redescendre jusque-là, sinon un `password` imbriqué passe.
            return $depth >= 2
                ? '[' . count($value) . ' entrée(s)]'
                : self::sanitize($value, $depth + 1);
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return self::cut(get_debug_type($value), 60);
    }

    /** D'où vient l'action : le panneau, l'API employé, une requête sans compte, une tâche. */
    private static function context(): string
    {
        if (app()->runningInConsole()) {
            return ActivityLog::CONTEXT_CONSOLE;
        }

        $path = request()->path();

        if (str_starts_with($path, 'api/')) {
            return ActivityLog::CONTEXT_API;
        }

        if (str_starts_with($path, 'admin') || str_starts_with($path, 'livewire')) {
            return ActivityLog::CONTEXT_PANEL;
        }

        return ActivityLog::CONTEXT_PUBLIC;
    }

    private static function ip(): ?string
    {
        try {
            $ip = request()->ip();
        } catch (Throwable) {
            return null;
        }

        return $ip === null ? null : self::cut($ip, 45);
    }

    private static function userAgent(): ?string
    {
        try {
            $agent = request()->userAgent();
        } catch (Throwable) {
            return null;
        }

        return $agent === null ? null : self::cut($agent, 512);
    }

    private static function cut(string $value, int $max): string
    {
        return mb_strlen($value) <= $max ? $value : mb_substr($value, 0, $max - 1) . '…';
    }
}
