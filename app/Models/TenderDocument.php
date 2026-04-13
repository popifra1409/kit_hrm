<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'name',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'version',
        'is_mandatory',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'is_mandatory' => 'boolean',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
