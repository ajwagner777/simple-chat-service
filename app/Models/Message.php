<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_room_id',
        'recipient_id',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeForConversation($query, int $userId1, int $userId2)
    {
        return $query->whereNull('chat_room_id')
            ->where(function ($q) use ($userId1, $userId2) {
                $q->where(function ($sq) use ($userId1, $userId2) {
                    $sq->where('user_id', $userId1)->where('recipient_id', $userId2);
                })->orWhere(function ($sq) use ($userId1, $userId2) {
                    $sq->where('user_id', $userId2)->where('recipient_id', $userId1);
                });
            });
    }
}
