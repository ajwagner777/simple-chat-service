<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_direct_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $token = auth('api')->login($sender);

        $response = $this->withToken($token)->postJson("/api/v1/direct-messages/{$recipient->id}", [
            'message' => 'Hey there!',
        ]);

        $response->assertStatus(201)->assertJsonFragment(['message' => 'Hey there!']);
        $this->assertDatabaseHas('direct_messages', [
            'sender_id'    => $sender->id,
            'recipient_id' => $recipient->id,
            'message'      => 'Hey there!',
        ]);
    }

    public function test_user_cannot_message_themselves(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson("/api/v1/direct-messages/{$user->id}", [
            'message' => 'Talking to myself',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_view_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $token = auth('api')->login($sender);

        // Send a message first
        $this->withToken($token)->postJson("/api/v1/direct-messages/{$recipient->id}", [
            'message' => 'Hello!',
        ]);

        $response = $this->withToken($token)->getJson("/api/v1/direct-messages/{$recipient->id}");
        $response->assertStatus(200)->assertJsonStructure(['data', 'total']);
    }
}
