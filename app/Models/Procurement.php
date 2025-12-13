<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'title',
        'procurement_type_id',
        'description',
        'procedure',
        'estimated_amount',
        'currency',
        'reserve_price',
        'requesting_department_id',
        'requesting_service_id',
        'initiated_by',
        'publication_date',
        'deadline_questions',
        'deadline_submission',
        'opening_date',
        'status',
        'approved_by_n1',
        'approved_by_n2',
        'approved_by_n3',
        'approved_at_n1',
        'approved_at_n2',
        'approved_at_n3',
        'requires_armp',
        'armp_reference',
        'armp_submission_date',
        'armp_status',
        'awarded_supplier_id',
        'awarded_amount',
        'award_date',
        'award_justification',
        'notes',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'awarded_amount' => 'decimal:2',
        'publication_date' => 'date',
        'deadline_questions' => 'date',
        'deadline_submission' => 'date',
        'opening_date' => 'date',
        'award_date' => 'date',
        'armp_submission_date' => 'date',
        'approved_at_n1' => 'datetime',
        'approved_at_n2' => 'datetime',
        'approved_at_n3' => 'datetime',
        'requires_armp' => 'boolean',
    ];

    // Relations
    public function procurementType()
    {
        return $this->belongsTo(ProcurementType::class);
    }

    public function requestingDepartment()
    {
        return $this->belongsTo(Department::class, 'requesting_department_id');
    }

    public function requestingService()
    {
        return $this->belongsTo(Service::class, 'requesting_service_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approverN1()
    {
        return $this->belongsTo(User::class, 'approved_by_n1');
    }

    public function approverN2()
    {
        return $this->belongsTo(User::class, 'approved_by_n2');
    }

    public function approverN3()
    {
        return $this->belongsTo(User::class, 'approved_by_n3');
    }

    public function awardedSupplier()
    {
        return $this->belongsTo(Supplier::class, 'awarded_supplier_id');
    }

    public function documents()
    {
        return $this->hasMany(TenderDocument::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function contract()
    {
        return $this->hasOne(ProcurementContract::class);
    }

    // Méthode pour générer une référence unique
    public static function generateReference()
    {
        $year = now()->year;
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'MP/' . $year . '/' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
