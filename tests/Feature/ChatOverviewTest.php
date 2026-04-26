<?php

namespace Tests\Feature;

use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_chat_overview(): void
    {
        $authUser = User::factory()->create();
        $token = auth('api')->login($authUser);

        $joinedRoom = ChatRoom::factory()->create(['owner_id' => $authUser->id]);
        $joinedRoom->users()->attach($authUser->id);

        $otherRoom = ChatRoom::factory()->create(['owner_id' => User::factory()->create()->id]);

        $dmUserA = User::factory()->create();
        $dmUserB = User::factory()->create();

        Message::create([
            'user_id' => $authUser->id,
            'recipient_id' => $dmUserA->id,
            'message' => 'older message',
        ]);

        Message::create([
            'user_id' => $dmUserA->id,
            'recipient_id' => $authUser->id,
            'message' => 'latest with A',
        ]);

        Message::create([
            'user_id' => $dmUserB->id,
            'recipient_id' => $authUser->id,
            'message' => 'latest with B',
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/chats/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'chat_rooms',
                'active_direct_messages',
            ])
            ->assertJsonCount(1, 'chat_rooms')
            ->assertJsonCount(2, 'active_direct_messages')
            ->assertJsonPath('chat_rooms.0.id', $joinedRoom->id)
            ->assertJsonPath('chat_rooms.0.name', $joinedRoom->name)
            ->assertJsonMissingPath('chat_rooms.1')
            ->assertJsonFragment(['message' => 'latest with A'])
            ->assertJsonFragment(['message' => 'latest with B']);
    }

    public function test_guest_cannot_get_chat_overview(): void
    {
        $response = $this->getJson('/api/v1/chats/overview');

        $response->assertStatus(401);
    }
}
