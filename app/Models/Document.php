<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'reference_number',
        'category_id',
        'type',
        'description',
        'summary',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'version',
        'previous_version_id',
        'is_latest_version',
        'issue_date',
        'effective_date',
        'expiry_date',
        'visibility',
        'allowed_roles',
        'allowed_departments',
        'requires_acknowledgment',
        'signed_by',
        'signed_date',
        'status',
        'created_by',
        'updated_by',
        'download_count',
        'view_count',
        'tags',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'signed_date' => 'date',
        'is_latest_version' => 'boolean',
        'requires_acknowledgment' => 'boolean',
        'allowed_roles' => 'array',
        'allowed_departments' => 'array',
        'tags' => 'array',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function previousVersion()
    {
        return $this->belongsTo(Document::class, 'previous_version_id');
    }

    public function versions()
    {
        return $this->hasMany(Document::class, 'previous_version_id');
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function acknowledgments()
    {
        return $this->hasMany(DocumentAcknowledgment::class);
    }

    // Méthodes
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getFileUrl()
    {
        return \Storage::url($this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getAcknowledgmentRate(): float
    {
        if (!$this->requires_acknowledgment) {
            return 0;
        }

        $totalEmployees = Employee::where('is_active', true)->count();

        if ($totalEmployees === 0) {
            return 0;
        }

        $acknowledged = $this->acknowledgments()->count();

        return round(($acknowledged / $totalEmployees) * 100, 2);
    }

    // Libellés
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'statute' => 'Statut',
            'regulation' => 'Règlement Intérieur',
            'policy' => 'Politique/Procédure',
            'memo' => 'Note de Service',
            'circular' => 'Circulaire',
            'announcement' => 'Communiqué',
            'contract_template' => 'Modèle de Contrat',
            'form' => 'Formulaire',
            'report' => 'Rapport',
            'other' => 'Autre',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'review' => 'En Révision',
            'approved' => 'Approuvé',
            'published' => 'Publié',
            'archived' => 'Archivé',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'review' => 'warning',
            'approved' => 'info',
            'published' => 'success',
            'archived' => 'danger',
            default => 'gray',
        };
    }

    public function getVisibilityLabel(): string
    {
        return match ($this->visibility) {
            'public' => 'Public',
            'restricted' => 'Restreint',
            'confidential' => 'Confidentiel',
            default => $this->visibility,
        };
    }
}
