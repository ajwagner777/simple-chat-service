<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatOverviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/chats/overview",
     *     summary="Get authenticated user's chat rooms and active direct message threads",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Chat overview",
     *         @OA\JsonContent(
     *             @OA\Property(property="chat_rooms", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="General"),
     *                     @OA\Property(property="description", type="string", example="General discussion"),
     *                     @OA\Property(property="is_private", type="boolean", example=false),
     *                     @OA\Property(property="owner", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="users_count", type="integer", example=5)
     *                 )
     *             ),
     *             @OA\Property(property="active_direct_messages", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="participant", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Jane Doe"),
     *                         @OA\Property(property="location", type="string", nullable=true, example="New York, NY")
     *                     ),
     *                     @OA\Property(property="last_message", type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="recipient_id", type="integer", example=2),
     *                         @OA\Property(property="message", type="string", example="Hey there!"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-26T12:00:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::guard('api')->user();
        $authId = $user->id;

        $chatRooms = $user->chatRooms()
            ->with('owner:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $directMessages = Message::query()
            ->whereNull('chat_room_id')
            ->where(function ($query) use ($authId) {
                $query->where('user_id', $authId)
                    ->orWhere('recipient_id', $authId);
            })
            ->with(['user:id,name,location', 'recipient:id,name,location'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $activeDirectMessages = $directMessages
            ->map(function (Message $message) use ($authId) {
                $participant = $message->user_id === $authId ? $message->recipient : $message->user;

                return [
                    'participant' => $participant?->only(['id', 'name', 'location']),
                    'last_message' => [
                        'id' => $message->id,
                        'user_id' => $message->user_id,
                        'recipient_id' => $message->recipient_id,
                        'message' => $message->message,
                        'created_at' => $message->created_at,
                    ],
                ];
            })
            ->filter(fn (array $thread) => !is_null($thread['participant']))
            ->unique(fn (array $thread) => $thread['participant']['id'])
            ->values();

        return response()->json([
            'chat_rooms' => $chatRooms,
            'active_direct_messages' => $activeDirectMessages,
        ]);
    }
}
