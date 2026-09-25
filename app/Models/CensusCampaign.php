<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusCampaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function submissions()
    {
        return $this->hasMany(CensusSubmission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * "Ouverte" au sens strict du statut en base (peut être devenue obsolète si
     * la date de fin est dépassée sans que la tâche planifiée soit encore passée).
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Une campagne est réellement accessible aux employés seulement si son statut
     * est "open" ET que sa date de fin (si renseignée) n'est pas dépassée. C'est
     * cette méthode qui doit être utilisée pour décider si le formulaire de
     * recensement se charge encore — effet immédiat, sans dépendre d'un cron.
     */
    public function isCurrentlyAccessible(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasEnded(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * La campagne actuellement accessible aux employés, s'il y en a une.
     */
    public static function currentlyOpen(): ?self
    {
        return static::where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest('starts_at')
            ->first();
    }

    /**
     * Une campagne "open" en base mais dont la date de fin est dépassée — utile
     * pour afficher un message précis à l'employé ("terminée" plutôt que "aucune
     * campagne"), même avant que la tâche planifiée ne l'ait officiellement clôturée.
     */
    public static function recentlyEnded(): ?self
    {
        return static::where('status', 'open')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->latest('ends_at')
            ->first();
    }
}
