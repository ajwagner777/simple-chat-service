<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        if (Schema::hasTable('chat_messages')) {
            DB::table('chat_messages')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    $payload = $rows->map(fn ($row) => [
                        'user_id' => $row->user_id,
                        'chat_room_id' => $row->chat_room_id,
                        'recipient_id' => null,
                        'message' => $row->message,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])->all();

                    if ($payload !== []) {
                        DB::table('messages')->insert($payload);
                    }
                });

            Schema::drop('chat_messages');
        }

        if (Schema::hasTable('direct_messages')) {
            DB::table('direct_messages')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    $payload = $rows->map(fn ($row) => [
                        'user_id' => $row->sender_id,
                        'chat_room_id' => null,
                        'recipient_id' => $row->recipient_id,
                        'message' => $row->message,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ])->all();

                    if ($payload !== []) {
                        DB::table('messages')->insert($payload);
                    }
                });

            Schema::drop('direct_messages');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_room_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('message');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('direct_messages')) {
            Schema::create('direct_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
                $table->text('message');
                $table->timestamps();
            });
        }

        DB::table('messages')
            ->whereNotNull('chat_room_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $payload = $rows->map(fn ($row) => [
                    'chat_room_id' => $row->chat_room_id,
                    'user_id' => $row->user_id,
                    'message' => $row->message,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])->all();

                if ($payload !== []) {
                    DB::table('chat_messages')->insert($payload);
                }
            });

        DB::table('messages')
            ->whereNull('chat_room_id')
            ->whereNotNull('recipient_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $payload = $rows->map(fn ($row) => [
                    'sender_id' => $row->user_id,
                    'recipient_id' => $row->recipient_id,
                    'message' => $row->message,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])->all();

                if ($payload !== []) {
                    DB::table('direct_messages')->insert($payload);
                }
            });

        Schema::drop('messages');
    }
};