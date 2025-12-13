<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DIPESubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_dipe',
        'cle_numero_dipe',
        'numero_contribuable',
        'month',
        'year',
        'type',
        'regime_cnps',
        'total_employees',
        'total_salaire_brut',
        'total_salaire_cotisable',
        'total_cotisations_cnps',
        'total_irpp',
        'file_path',
        'excel_path',
        'status',
        'submission_date',
        'submitted_by',
        'notes',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_employees' => 'integer',
        'total_salaire_brut' => 'decimal:2',
        'total_salaire_cotisable' => 'decimal:2',
        'total_cotisations_cnps' => 'decimal:2',
        'total_irpp' => 'decimal:2',
        'submission_date' => 'date',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
