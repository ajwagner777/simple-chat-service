<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     title="Simple Chat Service API",
 *     version="1.0.0",
 *     description="A Laravel-based REST API chat service with JWT auth, chat rooms, direct messaging, and WebSocket support.",
 *     @OA\Contact(email="admin@simplechat.local")
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Server(url="/api/v1", description="API v1")
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-25T12:00:00Z")
     *             ),
    *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
    *             @OA\Property(property="refresh_token", type="string", example="b8b3098d2b214b68b303d0cf84244d1f0b3ef8d40fd0458d914f8f49493c2fa7"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
    *             @OA\Property(property="expires_in", type="integer", example=3600),
    *             @OA\Property(property="refresh_token_expires_in", type="integer", example=2592000)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email has already been taken."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create($data);
        $token = Auth::guard('api')->login($user);

        try {
            Mail::to($user->email)->send(new \App\Mail\WelcomeMail($user));
        } catch (\Exception $e) {
            // Don't fail registration if email fails
        }

        return $this->respondWithToken($token, $user, 'User registered successfully.', 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Log in and receive a JWT token",
     *     tags={"Auth"},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful, returns JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
    *             @OA\Property(property="refresh_token", type="string", example="b8b3098d2b214b68b303d0cf84244d1f0b3ef8d40fd0458d914f8f49493c2fa7"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
    *             @OA\Property(property="expires_in", type="integer", example=3600),
    *             @OA\Property(property="refresh_token_expires_in", type="integer", example=2592000)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Log out (invalidate JWT token)",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user('api');

        Auth::guard('api')->logout();

        $user
            ?->refreshTokens()
            ->active()
            ->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Refresh JWT token",
     *     tags={"Auth"},
    *     @OA\RequestBody(required=true,
    *         @OA\JsonContent(required={"refresh_token"},
    *             @OA\Property(property="refresh_token", type="string", example="b8b3098d2b214b68b303d0cf84244d1f0b3ef8d40fd0458d914f8f49493c2fa7")
    *         )
    *     ),
     *     @OA\Response(response=200, description="Token refreshed",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
    *             @OA\Property(property="refresh_token", type="string", example="16f7144a565248fb82f00a4c77112d4a89fc40277b8a4973b0ab0c552b5f0e0f"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
    *             @OA\Property(property="expires_in", type="integer", example=3600),
    *             @OA\Property(property="refresh_token_expires_in", type="integer", example=2592000)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshToken = RefreshToken::query()
            ->active()
            ->where('token_hash', $this->hashRefreshToken($data['refresh_token']))
            ->first();

        if (!$refreshToken) {
            return response()->json(['message' => 'Invalid refresh token.'], 401);
        }

        $refreshToken->forceFill([
            'last_used_at' => now(),
            'revoked_at' => now(),
        ])->save();

        $token = Auth::guard('api')->login($refreshToken->user);

        return $this->respondWithToken($token, $refreshToken->user);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Get authenticated user",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Authenticated user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="location", type="string", example="New York, NY"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-25T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    /**
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     summary="Send password reset email",
     *     tags={"Auth"},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset link sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="We have emailed your password reset link.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Email not found or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="We can't find a user with that email address.")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json(
            ['message' => __($status)],
            $status === Password::RESET_LINK_SENT ? 200 : 422
        );
    }

    /**
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Reset password using token",
     *     tags={"Auth"},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"token","email","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", example="a1b2c3d4e5f6..."),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your password has been reset.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid token or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This password reset token is invalid.")
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    protected function respondWithToken(
        string $token,
        User $user,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $refreshToken = $this->issueRefreshToken($user);

        $payload = [
            'token' => $token,
            'refresh_token' => $refreshToken['plain_text_token'],
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'refresh_token_expires_in' => $refreshToken['expires_in'],
        ];

        if ($message !== null) {
            $payload['message'] = $message;
            $payload['user'] = $user;
        }

        return response()->json($payload, $status);
    }

    protected function issueRefreshToken(User $user): array
    {
        $plainTextToken = Str::random(80);
        $expiresAt = now()->addMinutes(config('auth.refresh_tokens.ttl'));

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->hashRefreshToken($plainTextToken),
            'expires_at' => $expiresAt,
        ]);

        return [
            'plain_text_token' => $plainTextToken,
            'expires_in' => now()->diffInSeconds($expiresAt),
        ];
    }

    protected function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
