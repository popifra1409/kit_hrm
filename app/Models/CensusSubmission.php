<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusSubmission extends Model
{
    protected $fillable = [
        'census_campaign_id',
        'employee_id',
        'status',
        'payload',
        'submitted_at',
        'career_validated_by',
        'career_validated_at',
        'career_rejection_reason',
        'solde_validated_by',
        'solde_validated_at',
        'solde_rejection_reason',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'submitted_at' => 'datetime',
        'career_validated_at' => 'datetime',
        'solde_validated_at' => 'datetime',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Statuts possibles, dans l'ordre du circuit :
     *   submitted         → en attente de validation Carrière
     *   career_validated  → en attente de validation Solde
     *   career_rejected   → renvoyé à l'employé (étape Carrière)
     *   solde_validated   → en attente de validation finale (admin)
     *   solde_rejected    → renvoyé à l'employé (étape Solde)
     *   validated         → validé définitivement, données appliquées
     *   rejected          → rejeté définitivement par l'admin
     */
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CAREER_VALIDATED = 'career_validated';
    public const STATUS_CAREER_REJECTED = 'career_rejected';
    public const STATUS_SOLDE_VALIDATED = 'solde_validated';
    public const STATUS_SOLDE_REJECTED = 'solde_rejected';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function campaign()
    {
        return $this->belongsTo(CensusCampaign::class, 'census_campaign_id');
    }

    public function careerValidatedBy()
    {
        return $this->belongsTo(User::class, 'career_validated_by');
    }

    public function soldeValidatedBy()
    {
        return $this->belongsTo(User::class, 'solde_validated_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // --- Lecture de l'étape courante ---

    public function isAwaitingCareer(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isAwaitingSolde(): bool
    {
        return $this->status === self::STATUS_CAREER_VALIDATED;
    }

    public function isAwaitingFinalAdmin(): bool
    {
        return $this->status === self::STATUS_SOLDE_VALIDATED;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isRejected(): bool
    {
        return in_array($this->status, [
            self::STATUS_CAREER_REJECTED,
            self::STATUS_SOLDE_REJECTED,
            self::STATUS_REJECTED,
        ], true);
    }

    /**
     * L'employé peut-il encore modifier et resoumettre (tant que ce n'est pas
     * validé définitivement) ?
     */
    public function canBeResubmitted(): bool
    {
        return $this->status !== self::STATUS_VALIDATED;
    }

    public function getRejectionReasonForEmployeeAttribute(): ?string
    {
        return match ($this->status) {
            self::STATUS_CAREER_REJECTED => $this->career_rejection_reason,
            self::STATUS_SOLDE_REJECTED => $this->solde_rejection_reason,
            self::STATUS_REJECTED => $this->rejection_reason,
            default => null,
        };
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'En attente de validation Carrière',
            self::STATUS_CAREER_VALIDATED => 'En attente de validation Solde',
            self::STATUS_CAREER_REJECTED => 'Rejeté — étape Carrière',
            self::STATUS_SOLDE_VALIDATED => 'En attente de validation finale (Administrateur)',
            self::STATUS_SOLDE_REJECTED => 'Rejeté — étape Solde',
            self::STATUS_VALIDATED => 'Validé définitivement',
            self::STATUS_REJECTED => 'Rejeté définitivement',
            default => $this->status,
        };
    }

    // --- Champs du payload par bucket (pour les écrans de revue) ---
    //
    // Structure du payload (inchangée dans sa forme, enrichie en contenu) :
    //   payload['personal']      → champs ci-dessous marqués (P), dont bank/cnps = Solde
    //   payload['organizational']→ tous les champs "declared_*"  = Carrière (jamais appliqués)
    //   payload['photo_path']    → photo de profil, appliquée à la validation finale
    //   payload['dependents'], payload['diplomas'] → toujours Carrière

    public const CAREER_FIELDS = [
        // Dans payload['personal'] — appliqués à la validation finale
        'matricule_fonction_publique',
        'category_number',
        'echelon_number',
        'computed_indice',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'marital_status',
        'children_under_6',
        'total_children',
        'id_card_number',
        'recruitment_date',
        'service_start_date',
        'phone',
        'email',
        'address',
        'city',
        // Dans payload['organizational']
        // — declared_department/declared_service : jamais appliqués (déclaratif)
        // — les 5 suivants : appliqués réellement à la validation finale
        'declared_department',
        'declared_service',
        'declared_job_title_id',
        'declared_trade_body_id',
        'declared_qualification_id',
        'declared_personnel_type',
        'declared_administrative_status',
    ];

    public const SOLDE_FIELDS = [
        // Dans payload['personal']
        'bank_name',
        'bank_account_number',
        'cnps_number',
    ];
}
