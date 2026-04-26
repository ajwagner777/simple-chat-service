<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectMessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/direct-messages/{userId}",
     *     summary="Get direct message conversation with a user",
     *     tags={"Direct Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated conversation messages",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="message", type="string", example="Hey there!"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(property="recipient", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Jane Smith")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-25T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="total", type="integer", example=25),
     *             @OA\Property(property="per_page", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User] 99")
     *         )
     *     )
     * )
     */
    public function conversation(User $user)
    {
        $authId = Auth::id();

        $messages = Message::forConversation($authId, $user->id)
            ->with(['user:id,name', 'recipient:id,name'])
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json($messages);
    }
    /**
     * @OA\Post(
     *     path="/direct-messages/{userId}",
     *     summary="Send a direct message to a user",
     *     tags={"Direct Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"message"},
     *             @OA\Property(property="message", type="string", example="Hey there!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Message sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Hey there!"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe")
     *             ),
     *             @OA\Property(property="recipient", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Smith")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-25T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User] 99")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Cannot message yourself",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot send a direct message to yourself.")
     *         )
     *     )
     * )
     */
    public function send(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot send a direct message to yourself.'], 422);
        }

        $data = $request->validate(['message' => 'required|string|max:5000']);

        $dm = Message::create([
            'user_id'      => Auth::id(),
            'recipient_id' => $user->id,
            'message'      => $data['message'],
        ]);

        $dm->load(['user:id,name', 'recipient:id,name']);

        broadcast(new DirectMessageSent($dm))->toOthers();

        return response()->json($dm, 201);
    }
}
