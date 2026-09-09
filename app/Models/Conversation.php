<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';

    protected $fillable = [
        'type',
        'name',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function activeParticipants()
    {
        return $this->hasMany(ConversationParticipant::class)->whereNull('left_at');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'left_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public static function findDirectBetween(int $userIdA, int $userIdB): ?self
    {
        return self::where('type', self::TYPE_DIRECT)
            ->whereHas('participants', fn($q) => $q->where('user_id', $userIdA))
            ->whereHas('participants', fn($q) => $q->where('user_id', $userIdB))
            ->first();
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();

        if (!$participant) {
            return 0;
        }

        $query = $this->messages();

        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->where('sender_id', '!=', $userId)->count();
    }
}
