<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'employee_id',
        'acknowledged_at',
        'ip_address',
        'comments',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    // Relations
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
