<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatRoomController;
use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });

        // Users
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/profile', [UserController::class, 'updateProfile']);

        // Chat rooms
        Route::get('chat-rooms', [ChatRoomController::class, 'index']);
        Route::post('chat-rooms', [ChatRoomController::class, 'store']);
        Route::get('chat-rooms/{chatRoom}', [ChatRoomController::class, 'show']);
        Route::post('chat-rooms/{chatRoom}/join', [ChatRoomController::class, 'join']);
        Route::post('chat-rooms/{chatRoom}/leave', [ChatRoomController::class, 'leave']);
        Route::get('chat-rooms/{chatRoom}/messages', [ChatRoomController::class, 'messages']);
        Route::post('chat-rooms/{chatRoom}/messages', [ChatRoomController::class, 'sendMessage']);

        // Direct messages
        Route::get('direct-messages/{user}', [DirectMessageController::class, 'conversation']);
        Route::post('direct-messages/{user}', [DirectMessageController::class, 'send']);
    });
});
