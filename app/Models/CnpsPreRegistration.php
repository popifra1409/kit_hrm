<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CnpsPreRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'employer_name',
        'employer_cnps_number',
        'employer_address',
        'employer_phone',
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'gender',
        'nationality',
        'id_type',
        'id_number',
        'id_issue_date',
        'id_expiry_date',
        'id_document_path',
        'address',
        'city',
        'region',
        'phone',
        'email',
        'marital_status',
        'number_of_children',
        'position_title',
        'hire_date',
        'monthly_salary',
        'contract_type',
        'cnps_category',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'beneficiaries',
        'birth_certificate_path',
        'marriage_certificate_path',
        'children_birth_certificates_path',
        'medical_certificate_path',
        'photo_path',
        'cnps_number',
        'cnps_registration_date',
        'status',
        'rejection_reason',
        'notes',
        'registration_form_path',
        'declaration_form_path',
        'created_by',
        'validated_by',
        'validated_at',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'id_issue_date' => 'date',
        'id_expiry_date' => 'date',
        'hire_date' => 'date',
        'cnps_registration_date' => 'date',
        'validated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'monthly_salary' => 'decimal:2',
        'beneficiaries' => 'array',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // Méthodes
    public function validate($validatedBy = null)
    {
        $this->status = 'pending';
        $this->validated_by = $validatedBy ?? auth()->id();
        $this->validated_at = now();
        $this->save();

        return $this;
    }

    public function submit($submittedBy = null)
    {
        $this->status = 'submitted';
        $this->submitted_by = $submittedBy ?? auth()->id();
        $this->submitted_at = now();
        $this->save();

        return $this;
    }

    public function approve($cnpsNumber)
    {
        $this->status = 'approved';
        $this->cnps_number = $cnpsNumber;
        $this->cnps_registration_date = now();
        $this->save();

        // Mettre à jour l'employé avec le numéro CNPS
        if ($this->employee) {
            $this->employee->update([
                'cnps_number' => $cnpsNumber,
            ]);
        }

        return $this;
    }

    public function reject($reason)
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->save();

        return $this;
    }

    // Libellés
    public function getGenderLabel(): string
    {
        return match ($this->gender) {
            'M' => 'Masculin',
            'F' => 'Féminin',
            default => $this->gender,
        };
    }

    public function getIdTypeLabel(): string
    {
        return match ($this->id_type) {
            'cni' => 'Carte Nationale d\'Identité',
            'passport' => 'Passeport',
            'residence_permit' => 'Titre de Séjour',
            default => $this->id_type,
        };
    }

    public function getMaritalStatusLabel(): string
    {
        return match ($this->marital_status) {
            'single' => 'Célibataire',
            'married' => 'Marié(e)',
            'divorced' => 'Divorcé(e)',
            'widowed' => 'Veuf/Veuve',
            default => $this->marital_status,
        };
    }

    public function getContractTypeLabel(): string
    {
        return match ($this->contract_type) {
            'permanent' => 'CDI',
            'fixed_term' => 'CDD',
            'temporary' => 'Temporaire',
            default => $this->contract_type,
        };
    }

    public function getCnpsCategoryLabel(): string
    {
        return match ($this->cnps_category) {
            'cadre_superieur' => 'Cadre Supérieur',
            'cadre_moyen' => 'Cadre Moyen',
            'agent_maitrise' => 'Agent de Maîtrise',
            'employe_qualifie' => 'Employé Qualifié',
            'employe' => 'Employé',
            'manoeuvre' => 'Manoeuvre',
            default => $this->cnps_category,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'pending' => 'En Attente',
            'submitted' => 'Soumise',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'completed' => 'Terminée',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending' => 'warning',
            'submitted' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'primary',
            default => 'gray',
        };
    }

    // Vérifier si tous les documents requis sont présents
    public function hasAllRequiredDocuments(): bool
    {
        $required = [
            'id_document_path',
            'birth_certificate_path',
            'photo_path',
        ];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // Si marié, certificat de mariage requis
        if ($this->marital_status === 'married' && empty($this->marriage_certificate_path)) {
            return false;
        }

        return true;
    }
}
