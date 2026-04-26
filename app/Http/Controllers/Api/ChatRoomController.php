<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChatRoomController extends Controller
{
    /**
     * @OA\Get(
     *     path="/chat-rooms",
     *     summary="List all public chat rooms",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of public chat rooms"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $rooms = ChatRoom::where('is_private', false)
            ->withCount('users')
            ->get();

        return response()->json($rooms);
    }

    /**
     * @OA\Post(
     *     path="/chat-rooms",
     *     summary="Create a new chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"name"},
     *             @OA\Property(property="name", type="string", example="General"),
     *             @OA\Property(property="description", type="string", example="General discussion"),
     *             @OA\Property(property="is_private", type="boolean", example=false),
     *             @OA\Property(property="password", type="string", description="Required if is_private=true", example="roompass")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Chat room created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private'  => 'boolean',
            'password'    => 'required_if:is_private,true|nullable|string|min:4',
        ]);

        $data['owner_id'] = Auth::id();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $room = ChatRoom::create($data);
        $room->users()->attach(Auth::id());

        return response()->json($room, 201);
    }

    /**
     * @OA\Get(
     *     path="/chat-rooms/{id}",
     *     summary="Get a specific chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Chat room details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(ChatRoom $chatRoom)
    {
        return response()->json($chatRoom->load('users:id,name'));
    }

    /**
     * @OA\Post(
     *     path="/chat-rooms/{id}/join",
     *     summary="Join a chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", description="Required for private rooms")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Joined successfully"),
     *     @OA\Response(response=403, description="Wrong password"),
     *     @OA\Response(response=409, description="Already a member")
     * )
     */
    public function join(Request $request, ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if ($chatRoom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already a member of this chat room.'], 409);
        }

        if ($chatRoom->is_private) {
            $request->validate(['password' => 'required|string']);
            if (!Hash::check($request->password, $chatRoom->password)) {
                return response()->json(['message' => 'Invalid room password.'], 403);
            }
        }

        $chatRoom->users()->attach($user->id);

        return response()->json(['message' => 'Joined chat room successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/chat-rooms/{id}/leave",
     *     summary="Leave a chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Left successfully or room deleted"),
     *     @OA\Response(response=404, description="Not a member")
     * )
     */
    public function leave(ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Not a member of this chat room.'], 404);
        }

        $chatRoom->users()->detach($user->id);

        if ($chatRoom->users()->count() === 0) {
            $chatRoom->delete();
            return response()->json(['message' => 'Left chat room. Room deleted (no members remaining).']);
        }

        return response()->json(['message' => 'Left chat room successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/chat-rooms/{id}/messages",
     *     summary="Get messages in a chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of messages"),
     *     @OA\Response(response=403, description="Must be a member to view messages")
     * )
     */
    public function messages(ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You must join the room to view messages.'], 403);
        }

        $messages = $chatRoom->messages()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * @OA\Post(
     *     path="/chat-rooms/{id}/messages",
     *     summary="Send a message to a chat room",
     *     tags={"Chat Rooms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"message"},
     *             @OA\Property(property="message", type="string", example="Hello everyone!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Message sent"),
     *     @OA\Response(response=403, description="Must be a member to send messages")
     * )
     */
    public function sendMessage(Request $request, ChatRoom $chatRoom)
    {
        $user = Auth::user();

        if (!$chatRoom->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You must join the room to send messages.'], 403);
        }

        $data = $request->validate(['message' => 'required|string|max:5000']);

        $message = ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'user_id'      => $user->id,
            'message'      => $data['message'],
        ]);

        $message->load('user:id,name');

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
