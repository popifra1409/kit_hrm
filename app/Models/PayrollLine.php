<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'payroll_item_id',
        'item_name',
        'type',
        'is_taxable',
        'is_subject_to_cnps',
        'amount',
        'display_order',
        'notes',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_subject_to_cnps' => 'boolean',
        'amount' => 'decimal:2',
        'display_order' => 'integer',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
