<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DirectMessage $dm) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('direct-message.' . $this->dm->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
