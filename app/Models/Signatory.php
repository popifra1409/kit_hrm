<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signatory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'position',
        'document_type',
        'signature_order',
        'signature_path',
        'stamp_path',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'signature_order' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Récupérer les signataires pour un type de document
    public static function getForDocumentType($documentType)
    {
        return self::where('document_type', $documentType)
            ->where('is_active', true)
            ->orderBy('signature_order')
            ->get();
    }

    // URL de la signature
    public function getSignatureUrlAttribute()
    {
        if ($this->signature_path) {
            return \Storage::url($this->signature_path);
        }
        return null;
    }

    // URL du cachet
    public function getStampUrlAttribute()
    {
        if ($this->stamp_path) {
            return \Storage::url($this->stamp_path);
        }
        return null;
    }
}
