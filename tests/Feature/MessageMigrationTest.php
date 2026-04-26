<?php

namespace Tests\Feature;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessageMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_message_tables_are_migrated_into_messages_table(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $room = ChatRoom::factory()->create(['owner_id' => $sender->id]);

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_room_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('direct_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('recipient_id');
            $table->text('message');
            $table->timestamps();
        });

        DB::table('chat_messages')->insert([
            'chat_room_id' => $room->id,
            'user_id' => $sender->id,
            'message' => 'Legacy room message',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('direct_messages')->insert([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message' => 'Legacy direct message',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_04_26_010000_migrate_legacy_messages_to_messages_table.php');
        $migration->up();

        $this->assertDatabaseHas('messages', [
            'user_id' => $sender->id,
            'chat_room_id' => $room->id,
            'recipient_id' => null,
            'message' => 'Legacy room message',
        ]);

        $this->assertDatabaseHas('messages', [
            'user_id' => $sender->id,
            'chat_room_id' => null,
            'recipient_id' => $recipient->id,
            'message' => 'Legacy direct message',
        ]);

        $this->assertFalse(Schema::hasTable('chat_messages'));
        $this->assertFalse(Schema::hasTable('direct_messages'));
    }
}
