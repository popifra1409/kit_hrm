<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une action ou une tentative, écrite une fois puis jamais modifiée.
 *
 * Le journal n'est pas une table métier : il n'a ni SoftDeletes ni updated_at, et ses
 * colonnes descriptives sont des instantanés pour rester lisible après la suppression
 * de l'agent ou du dossier concerné.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'occurred_at',
        'actor_user_id',
        'actor_matricule',
        'actor_name',
        'context',
        'action',
        'status',
        'subject_type',
        'subject_id',
        'subject_label',
        'reason',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'properties' => 'array',
    ];

    public const CONTEXT_PANEL = 'panel';
    public const CONTEXT_API = 'api';
    public const CONTEXT_PUBLIC = 'public';
    public const CONTEXT_CONSOLE = 'console';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED = 'blocked';

    public const CONTEXTS = [
        self::CONTEXT_PANEL => 'Back-office',
        self::CONTEXT_API => 'Espace employé',
        self::CONTEXT_PUBLIC => 'Sans connexion',
        self::CONTEXT_CONSOLE => 'Tâche planifiée',
    ];

    public const STATUSES = [
        self::STATUS_SUCCESS => 'Aboutie',
        self::STATUS_FAILED => 'Échouée',
        self::STATUS_BLOCKED => 'Bloquée',
    ];

    /**
     * Libellés français des verbes. Un verbe absent s'affiche tel quel : mieux vaut une
     * ligne brute qu'une ligne muette, un agent doit pouvoir lire ce qu'il a sous les yeux.
     */
    public const ACTIONS = [
        'login_success' => 'Connexion réussie',
        'login_failed' => 'Identifiants incorrects',
        'login_locked' => 'Blocage temporaire après trop d’échecs',
        'logout' => 'Déconnexion',
        'activation_requested' => 'Demande d’activation de compte',
        'activation_started' => 'Étape 1 de l’activation franchie',
        'activation_failed' => 'Vérification d’activation échouée',
        'activation_locked' => 'Démarche d’activation bloquée après trop d’échecs',
        'activation_validated' => 'Activation validée',
        'activation_rejected' => 'Activation rejetée',
        'password_reset_requested' => 'Demande de réinitialisation du mot de passe',
        'password_reset_done' => 'Mot de passe réinitialisé par le Service RH',
        'password_changed' => 'Mot de passe modifié',
        'account_deletion_requested' => 'Suppression de compte demandée',
        'census_submitted' => 'Recensement déposé',
        'census_corrected' => 'Recensement corrigé par le Service RH',
        'census_validated' => 'Recensement validé',
        'census_rejected' => 'Recensement renvoyé pour correction',
        'census_canceled' => 'Recensement annulé',
        'direct_entry_blocked' => 'Ajout direct bloqué par le recensement en cours',
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
        'archived' => 'Versement aux archives',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getContextLabelAttribute(): string
    {
        return self::CONTEXTS[$this->context] ?? $this->context;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Ce qu'un agent lit dans la colonne « Acteur » : le matricule prime, car le compte
     * a peut-être été supprimé depuis.
     */
    public function getActorLabelAttribute(): string
    {
        if ($this->actor_matricule) {
            return trim("{$this->actor_name} ({$this->actor_matricule})");
        }

        return $this->actor_name ?: '—';
    }

    public function scopeSince($query, ?string $from)
    {
        return $from ? $query->where('occurred_at', '>=', $from) : $query;
    }

    /**
     * Les détails en clair, une ligne par information — c'est ce que l'agent lit dans la
     * fenêtre « Détail » du journal. Un diff d'édition s'affiche sous forme
     * « avant : … / après : … », ce qui se relit sans décrypter du JSON.
     */
    public static function describeProperties(?array $properties): string
    {
        if (empty($properties)) {
            return '—';
        }

        $lignes = [];

        foreach ($properties as $cle => $valeur) {
            if (is_array($valeur)) {
                $sous = [];

                foreach ($valeur as $sousCle => $sousValeur) {
                    $sous[] = (is_int($sousCle) ? '' : $sousCle . ' = ') . self::printValue($sousValeur);
                }

                $lignes[] = $cle . ' : ' . ($sous === [] ? '—' : implode(' · ', $sous));

                continue;
            }

            $lignes[] = $cle . ' : ' . self::printValue($valeur);
        }

        return implode("\n", $lignes);
    }

    private static function printValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }

        if (is_array($value)) {
            return implode(', ', array_map([self::class, 'printValue'], $value));
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
