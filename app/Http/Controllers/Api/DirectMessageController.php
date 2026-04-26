<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\DirectMessage;
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
     *     @OA\Response(response=200, description="Paginated conversation messages"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function conversation(User $user)
    {
        $authId = Auth::id();

        $messages = DirectMessage::where(function ($q) use ($authId, $user) {
            $q->where('sender_id', $authId)->where('recipient_id', $user->id);
        })->orWhere(function ($q) use ($authId, $user) {
            $q->where('sender_id', $user->id)->where('recipient_id', $authId);
        })
          ->with(['sender:id,name', 'recipient:id,name'])
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
     *     @OA\Response(response=201, description="Message sent"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Cannot message yourself")
     * )
     */
    public function send(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot send a direct message to yourself.'], 422);
        }

        $data = $request->validate(['message' => 'required|string|max:5000']);

        $dm = DirectMessage::create([
            'sender_id'    => Auth::id(),
            'recipient_id' => $user->id,
            'message'      => $data['message'],
        ]);

        $dm->load(['sender:id,name', 'recipient:id,name']);

        broadcast(new DirectMessageSent($dm))->toOthers();

        return response()->json($dm, 201);
    }
}
