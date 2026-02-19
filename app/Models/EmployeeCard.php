<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EmployeeCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'card_type',
        'card_number',
        'qr_code_path',
        'qr_code_data',
        'issue_date',
        'expiry_date',
        'is_active',
        'status',
        'activated_by',
        'activated_at',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'card_pdf_path',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'activated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Méthodes
    public function generateCardNumber(): string
    {
        $prefix = $this->card_type === 'professional' ? 'PROF' : 'SANTE';
        $number = $prefix . '-' . $this->employee->matricule . '-' . date('Y');

        $this->card_number = $number;
        $this->save();

        return $number;
    }

    public function generateQrCode(): void
    {
        // Données à encoder
        $data = [
            'type' => $this->card_type,
            'card_number' => $this->card_number,
            'matricule' => $this->employee->matricule,
            'name' => $this->employee->full_name,
            'birth_date' => $this->employee->birth_date->format('d/m/Y'),
            'issued' => $this->issue_date->format('d/m/Y'),
            'expires' => $this->expiry_date->format('d/m/Y'),
        ];

        $this->qr_code_data = json_encode($data);

        // Générer l'image QR
        $filename = 'qrcodes/card-' . $this->card_number . '.png';
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($this->qr_code_data);

        Storage::disk('public')->put($filename, $qrCode);

        $this->qr_code_path = $filename;
        $this->save();
    }

    public function activate($userId = null): void
    {
        $this->is_active = true;
        $this->status = 'active';
        $this->activated_by = $userId ?? auth()->id();
        $this->activated_at = now();
        $this->save();
    }

    public function revoke($reason, $userId = null): void
    {
        $this->is_active = false;
        $this->status = 'revoked';
        $this->revoked_by = $userId ?? auth()->id();
        $this->revoked_at = now();
        $this->revocation_reason = $reason;
        $this->save();
    }

    // Libellés
    public function getCardTypeLabel(): string
    {
        return match ($this->card_type) {
            'professional' => 'Carte Professionnelle',
            'health_coverage' => 'Carte de Prise en Charge',
            default => $this->card_type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'issued' => 'Émise',
            'active' => 'Active',
            'suspended' => 'Suspendue',
            'expired' => 'Expirée',
            'revoked' => 'Révoquée',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'issued' => 'info',
            'active' => 'success',
            'suspended' => 'warning',
            'expired' => 'danger',
            'revoked' => 'danger',
            default => 'gray',
        };
    }
}
