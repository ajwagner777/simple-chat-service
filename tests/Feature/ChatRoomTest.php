<?php

namespace Tests\Feature;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatRoomTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): array
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        return [$user, $token];
    }

    public function test_user_can_list_public_rooms(): void
    {
        [, $token] = $this->actingAsUser();
        ChatRoom::factory()->create(['is_private' => false, 'owner_id' => User::factory()->create()->id]);

        $response = $this->withToken($token)->getJson('/api/v1/chat-rooms');
        $response->assertStatus(200)->assertJsonStructure([['id', 'name', 'is_private']]);
    }

    public function test_user_can_create_public_room(): void
    {
        [, $token] = $this->actingAsUser();

        $response = $this->withToken($token)->postJson('/api/v1/chat-rooms', [
            'name'       => 'General',
            'is_private' => false,
        ]);

        $response->assertStatus(201)->assertJsonFragment(['name' => 'General']);
        $this->assertDatabaseHas('chat_rooms', ['name' => 'General']);
    }

    public function test_user_can_create_private_room(): void
    {
        [, $token] = $this->actingAsUser();

        $response = $this->withToken($token)->postJson('/api/v1/chat-rooms', [
            'name'       => 'Secret Room',
            'is_private' => true,
            'password'   => 'secret1234',
        ]);

        $response->assertStatus(201)->assertJsonFragment(['name' => 'Secret Room']);
    }

    public function test_user_can_join_public_room(): void
    {
        [$owner, ] = $this->actingAsUser();
        [$joiner, $token] = $this->actingAsUser();

        $room = ChatRoom::factory()->create(['is_private' => false, 'owner_id' => $owner->id]);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/join");
        $response->assertStatus(200)->assertJsonFragment(['message' => 'Joined chat room successfully.']);
    }

    public function test_user_cannot_join_private_room_without_password(): void
    {
        [$owner, ] = $this->actingAsUser();
        [$joiner, $token] = $this->actingAsUser();

        $room = ChatRoom::factory()->create([
            'is_private' => true,
            'password'   => Hash::make('secret123'),
            'owner_id'   => $owner->id,
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/join");
        $response->assertStatus(422);
    }

    public function test_user_can_join_private_room_with_correct_password(): void
    {
        [$owner, ] = $this->actingAsUser();
        [$joiner, $token] = $this->actingAsUser();

        $room = ChatRoom::factory()->create([
            'is_private' => true,
            'password'   => Hash::make('secret123'),
            'owner_id'   => $owner->id,
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/join", [
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
    }

    public function test_user_can_leave_room_and_room_is_deleted_when_empty(): void
    {
        [$user, $token] = $this->actingAsUser();

        $room = ChatRoom::factory()->create(['is_private' => false, 'owner_id' => $user->id]);
        $room->users()->attach($user->id);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/leave");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('chat_rooms', ['id' => $room->id]);
    }

    public function test_member_can_send_message_to_room(): void
    {
        [$user, $token] = $this->actingAsUser();
        $room = ChatRoom::factory()->create(['is_private' => false, 'owner_id' => $user->id]);
        $room->users()->attach($user->id);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/messages", [
            'message' => 'Hello, world!',
        ]);

        $response->assertStatus(201)->assertJsonFragment(['message' => 'Hello, world!']);
    }

    public function test_non_member_cannot_send_message(): void
    {
        [$owner, ] = $this->actingAsUser();
        [$other, $token] = $this->actingAsUser();

        $room = ChatRoom::factory()->create(['is_private' => false, 'owner_id' => $owner->id]);

        $response = $this->withToken($token)->postJson("/api/v1/chat-rooms/{$room->id}/messages", [
            'message' => 'Intruder message',
        ]);

        $response->assertStatus(403);
    }
}
