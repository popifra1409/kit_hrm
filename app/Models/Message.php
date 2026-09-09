<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'edited_at',
        'deleted_for_everyone_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_for_everyone_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Message $message) {
            $message->conversation()->update(['last_message_at' => $message->created_at]);
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }
}
