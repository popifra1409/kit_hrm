<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'threshold_aon',
        'threshold_aoi',
        'requires_armp_approval',
        'min_publication_days',
        'is_active',
    ];

    protected $casts = [
        'threshold_aon' => 'decimal:2',
        'threshold_aoi' => 'decimal:2',
        'requires_armp_approval' => 'boolean',
        'min_publication_days' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relations
    public function procurements()
    {
        return $this->hasMany(Procurement::class);
    }

    // Méthodes utilitaires
    public function getCategoryLabelAttribute()
    {
        return match ($this->category) {
            'works' => 'Travaux',
            'goods' => 'Fournitures',
            'services' => 'Services',
            'consulting' => 'Conseils/Études',
            default => $this->category,
        };
    }

    public function shouldRequireARMP($amount)
    {
        if (!$this->requires_armp_approval) {
            return false;
        }

        // Vérifier si le montant dépasse les seuils
        if ($amount >= $this->threshold_aoi) {
            return true; // AOI nécessite toujours ARMP
        }

        if ($amount >= $this->threshold_aon) {
            return true; // AON nécessite ARMP
        }

        return false;
    }

    public function getRecommendedProcedure($amount)
    {
        if ($amount >= $this->threshold_aoi) {
            return 'open_tender'; // Appel d'offres international
        }

        if ($amount >= $this->threshold_aon) {
            return 'open_tender'; // Appel d'offres national
        }

        if ($amount >= ($this->threshold_aon * 0.5)) {
            return 'consultation'; // Consultation
        }

        return 'request_for_quote'; // Demande de cotation
    }
}
