<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountActivationRequest extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'employee_id',
        'matricule',
        'type',
        'requested_password_hash',
        'self_declared_date',
        'status',
        'rejection_reason',
        'validated_by',
        'validated_at',
        'expires_at',
    ];

    protected $casts = [
        'self_declared_date' => 'date',
        'validated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** Le mot de passe en attente ne doit jamais sortir d'ici. */
    protected $hidden = [
        'requested_password_hash',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    /** Compte jamais ouvert : l'agent applique le mot de passe choisi par l'employé. */
    public const TYPE_ACTIVATION = 'activation';

    /** Mot de passe oublié : l'agent génère un mot de passe temporaire à remettre. */
    public const TYPE_PASSWORD_RESET = 'password_reset';

    /** Passé ce délai, le mot de passe choisi n'est plus applicable : l'employé relance sa demande. */
    public const VALIDITY_DAYS = 7;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Validable = encore en attente et dans son délai de vie. Une demande expirée
     * reste visible pour l'historique mais ne doit plus ouvrir de compte.
     */
    public function isValidatable(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function isPasswordReset(): bool
    {
        return $this->type === self::TYPE_PASSWORD_RESET;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => $this->isExpired() ? 'Expirée' : 'En attente',
            self::STATUS_VALIDATED => 'Validée',
            self::STATUS_REJECTED => 'Rejetée',
            default => $this->status,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PASSWORD_RESET => 'Réinitialisation du mot de passe',
            default => 'Activation du compte',
        };
    }

    /**
     * Référence courte, lisible à voix haute au Service RH, sans lettres ambiguës.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'RH-' . $suffix;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
