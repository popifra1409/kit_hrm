<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusCampaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function submissions()
    {
        return $this->hasMany(CensusSubmission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public static function currentlyOpen(): ?self
    {
        return static::where('status', 'open')->latest('starts_at')->first();
    }
}
