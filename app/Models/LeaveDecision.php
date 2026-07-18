<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveDecision extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type',
        'decision_date',
        'decision_number',
        'start_date',
        'end_date',
        'duration_days',
        'description',
        'decision_document_path',
        'signed_by',
        'signed_at',
        'status'
    ];

    protected $dates = ['decision_date', 'start_date', 'end_date', 'signed_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->decision_number) {
                $model->decision_number = 'DEC-' . now()->year . '-' . str_pad(
                    self::where('decision_date', '>=', now()->startOfYear())->count() + 1,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function sign(User $user)
    {
        $this->update([
            'signed_by' => $user->id,
            'signed_at' => now(),
            'status' => 'signed',
        ]);
    }
}
