<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'contract_type_id',
        'contract_number',
        'start_date',
        'end_date',
        'signature_date',
        'salary',
        'position',
        'work_location',
        'terms',
        'document_path',
        'renewal_count',
        'renewed_from_id',
        'status',
        'termination_date',
        'termination_reason',
        'is_current',
        'notes',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signature_date' => 'date',
        'termination_date' => 'date',
        'validated_at' => 'datetime',
        'salary' => 'decimal:2',
        'renewal_count' => 'integer',
        'is_current' => 'boolean',
    ];

    protected $appends = [
        'duration_in_months',
        'remaining_days',
        'is_expiring_soon',
    ];

    // ========================================
    // RELATIONS
    // ========================================

    /**
     * Employé lié au contrat
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Type de contrat (CDI, CDD, Stage, etc.)
     */
    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    /**
     * Contrat précédent (en cas de renouvellement)
     */
    public function renewedFrom()
    {
        return $this->belongsTo(Contract::class, 'renewed_from_id');
    }

    /**
     * Contrats renouvelés depuis celui-ci
     */
    public function renewals()
    {
        return $this->hasMany(Contract::class, 'renewed_from_id');
    }

    /**
     * Utilisateur ayant validé le contrat
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Contrats actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Contrats expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Contrats résiliés
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    /**
     * Contrats en brouillon
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Contrats renouvelés
     */
    public function scopeRenewed($query)
    {
        return $query->where('status', 'renewed');
    }

    /**
     * Contrats actuels (is_current = true)
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Contrats expirant bientôt (dans X jours)
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Contrats d'un type spécifique
     */
    public function scopeByType($query, $contractTypeId)
    {
        return $query->where('contract_type_id', $contractTypeId);
    }

    /**
     * Contrats validés
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    /**
     * Contrats CDI (sans date de fin)
     */
    public function scopeCdi($query)
    {
        return $query->whereNull('end_date');
    }

    /**
     * Contrats CDD (avec date de fin)
     */
    public function scopeCdd($query)
    {
        return $query->whereNotNull('end_date');
    }

    // ========================================
    // ACCESSORS & MUTATORS
    // ========================================

    /**
     * Durée du contrat en mois
     */
    public function getDurationInMonthsAttribute()
    {
        if (!$this->end_date) {
            return null; // CDI - pas de limite
        }
        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Nombre de jours restants avant expiration
     */
    public function getRemainingDaysAttribute()
    {
        if (!$this->end_date || $this->isExpired()) {
            return 0;
        }

        $remaining = now()->diffInDays($this->end_date, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Le contrat expire bientôt (moins de 30 jours)
     */
    public function getIsExpiringSoonAttribute()
    {
        return $this->remaining_days > 0 && $this->remaining_days <= 30;
    }

    /**
     * Durée du contrat formatée
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->end_date) {
            return 'Durée indéterminée (CDI)';
        }

        $months = $this->duration_in_months;
        $years = floor($months / 12);
        $remainingMonths = $months % 12;

        if ($years > 0 && $remainingMonths > 0) {
            return "{$years} an(s) et {$remainingMonths} mois";
        } elseif ($years > 0) {
            return "{$years} an(s)";
        } else {
            return "{$months} mois";
        }
    }

    /**
     * Badge de statut coloré
     */
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'draft' => '📝 Brouillon',
            'active' => '✅ Actif',
            'expired' => '⏰ Expiré',
            'terminated' => '❌ Résilié',
            'renewed' => '🔄 Renouvelé',
            default => $this->status,
        };
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Le contrat est-il actif ?
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Le contrat est-il expiré ?
     */
    public function isExpired()
    {
        return $this->status === 'expired' ||
            ($this->end_date && $this->end_date->isPast());
    }

    /**
     * Le contrat est-il un CDI ?
     */
    public function isCdi()
    {
        return $this->end_date === null;
    }

    /**
     * Le contrat est-il un CDD ?
     */
    public function isCdd()
    {
        return $this->end_date !== null;
    }

    /**
     * Le contrat peut-il être renouvelé ?
     */
    public function canBeRenewed()
    {
        if (!$this->contractType) {
            return false;
        }

        return $this->contractType->renewable &&
            $this->status === 'active' &&
            $this->isCdd();
    }

    /**
     * Le contrat a-t-il atteint sa durée maximale ?
     */
    public function hasReachedMaxDuration()
    {
        if (!$this->contractType || !$this->contractType->max_duration_months) {
            return false;
        }

        $totalMonths = $this->duration_in_months ?? 0;
        return $totalMonths >= $this->contractType->max_duration_months;
    }

    /**
     * Générer un numéro de contrat automatique
     */
    public static function generateContractNumber()
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'CONT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Renouveler le contrat
     */
    public function renew($newEndDate, $newSalary = null)
    {
        if (!$this->canBeRenewed()) {
            throw new \Exception('Ce contrat ne peut pas être renouvelé.');
        }

        // Marquer le contrat actuel comme renouvelé
        $this->update([
            'status' => 'renewed',
            'is_current' => false,
        ]);

        // Créer le nouveau contrat
        $newContract = static::create([
            'employee_id' => $this->employee_id,
            'contract_type_id' => $this->contract_type_id,
            'contract_number' => static::generateContractNumber(),
            'start_date' => $this->end_date->addDay(),
            'end_date' => $newEndDate,
            'salary' => $newSalary ?? $this->salary,
            'position' => $this->position,
            'work_location' => $this->work_location,
            'terms' => $this->terms,
            'renewal_count' => $this->renewal_count + 1,
            'renewed_from_id' => $this->id,
            'status' => 'draft',
            'is_current' => true,
        ]);

        return $newContract;
    }

    /**
     * Résilier le contrat
     */
    public function terminate($reason, $terminationDate = null)
    {
        $this->update([
            'status' => 'terminated',
            'termination_reason' => $reason,
            'termination_date' => $terminationDate ?? now(),
            'is_current' => false,
        ]);
    }

    /**
     * Valider le contrat
     */
    public function validate($userId = null)
    {
        $this->update([
            'status' => 'active',
            'validated_by' => $userId ?? auth()->id(),
            'validated_at' => now(),
            'is_current' => true,
        ]);
    }

    /**
     * Vérifier et mettre à jour le statut si expiré
     */
    public function checkAndUpdateExpiration()
    {
        if ($this->status === 'active' && $this->end_date && $this->end_date->isPast()) {
            $this->update([
                'status' => 'expired',
                'is_current' => false,
            ]);
            return true;
        }
        return false;
    }

    // ========================================
    // EVENTS
    // ========================================

    protected static function boot()
    {
        parent::boot();

        // Générer automatiquement un numéro de contrat si non fourni
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = static::generateContractNumber();
            }
        });

        // Vérifier qu'un seul contrat est "current" par employé
        static::saving(function ($contract) {
            if ($contract->is_current && $contract->employee_id) {
                static::where('employee_id', $contract->employee_id)
                    ->where('id', '!=', $contract->id)
                    ->update(['is_current' => false]);
            }
        });
    }
}
