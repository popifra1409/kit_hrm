<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Trace les écritures des modèles surveillés : création, modification (avec les
 * anciennes et nouvelles valeurs), suppression, restauration.
 *
 * Le diff est borné et assaini par ActivityLogger ; ce qui ne doit jamais être consigné
 * (mots de passe, tokens, pièces téléversées) est écarté là-bas, une seule fois.
 */
class ActivityLogObserver
{
    /** Colonnes sans intérêt d'audit, ou qui cacheraient un secret. */
    private const IGNORED = [
        'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password',
        'payload', 'token', 'id', 'updated_by', 'created_by',
        // Le portail réécrit ce réglage à chaque changement de langue ou de police : une
        // tracasserie d'apparence, pas un événement RH. Une vraie modification du compte
        // (rôle, mail, activation) passe par d'autres colonnes et reste consignée.
        'preferences',
    ];

    public function created(Model $model): void
    {
        ActivityLogger::record(
            action: 'created',
            subject: $model,
            properties: $this->attributes($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->attributes($model->getChanges());

        if ($changes === []) {
            return;
        }

        $before = [];

        foreach (array_keys($changes) as $key) {
            $before[$key] = $model->getOriginal($key);
        }

        ActivityLogger::record(
            action: 'updated',
            subject: $model,
            properties: [
                'avant' => $this->attributes($before),
                'apres' => $changes,
            ],
            description: implode(', ', array_keys($changes)),
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::record(
            action: 'deleted',
            subject: $model,
            properties: ['effacement' => $this->deletionKind($model)],
        );
    }

    public function restored(Model $model): void
    {
        ActivityLogger::record(
            action: 'restored',
            subject: $model,
            description: class_basename($model),
        );
    }

    /**
     * Les colonnes techniques sortent avant tout : une modification de `payload` seul ne
     * doit pas encombrer le journal, et `password` ne doit jamais y paraître.
     */
    private function attributes(array $values): array
    {
        return array_diff_key($values, array_flip(self::IGNORED));
    }

    private function deletionKind(Model $model): string
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            return 'logique';
        }

        return 'définitif';
    }
}
