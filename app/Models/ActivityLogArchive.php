<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ligne versée dans l'archive par activity-logs:archive.
 *
 * Aucune clé étrangère : l'archive doit rester lisible même après la suppression des
 * comptes et des dossiers qu'elle décrit.
 */
class ActivityLogArchive extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs_archive';

    protected $fillable = [
        'occurred_at',
        'period',
        'archived_at',
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
        'archived_at' => 'datetime',
        'properties' => 'array',
    ];

    public function getActionLabelAttribute(): string
    {
        return ActivityLog::ACTIONS[$this->action] ?? $this->action;
    }

    public function getContextLabelAttribute(): string
    {
        return ActivityLog::CONTEXTS[$this->context] ?? $this->context;
    }

    public function getStatusLabelAttribute(): string
    {
        return ActivityLog::STATUSES[$this->status] ?? $this->status;
    }

    public function getActorLabelAttribute(): string
    {
        if ($this->actor_matricule) {
            return trim("{$this->actor_name} ({$this->actor_matricule})");
        }

        return $this->actor_name ?: '—';
    }
}
