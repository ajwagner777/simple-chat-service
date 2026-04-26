<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
     *     @OA\Response(response=200, description="Profile updated"),
     *     @OA\Response(response=401, description="Unauthenticated")
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
