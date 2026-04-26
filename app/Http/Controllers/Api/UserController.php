<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/users",
     *     summary="List all users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="location", type="string", example="New York, NY")
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
        $currentId = Auth::guard('api')->id();

        $users = User::where('id', '!=', $currentId)
            ->select('id', 'name', 'location')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="View a user's profile",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=2),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="location", type="string", example="New York, NY")
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show(User $user)
    {
        return response()->json($user->only('id', 'name', 'location'));
    }

    /**
     * @OA\Put(
     *     path="/users/profile",
     *     summary="Update authenticated user profile",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="location", type="string", example="New York, NY")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile updated."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="location", type="string", example="New York, NY"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-25T12:30:00Z")
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
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
        ]);

        $user->update($data);

        return response()->json(['message' => 'Profile updated.', 'user' => $user->fresh()]);
    }
}
