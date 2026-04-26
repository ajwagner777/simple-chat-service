<?php

use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat-room.{roomId}', function ($user, $roomId) {
    $room = ChatRoom::find($roomId);
    if ($room && $room->users()->where('user_id', $user->id)->exists()) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    return false;
});

Broadcast::channel('direct-message.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
