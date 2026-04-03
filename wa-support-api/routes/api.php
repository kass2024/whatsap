<?php

use App\Http\Controllers\Api\AdminPhoneSettingsController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PushDeviceController;
use App\Http\Controllers\Api\TemplateMessageController;
use Illuminate\Support\Facades\Route;

/*
 * Opening /api/login in a browser sends GET — login is POST-only. This avoids a 405 when
 * someone pastes the URL. Use the web app at /login or POST JSON from the mobile app.
 */
Route::get('/login', function () {
    return response()->json([
        'message' => 'Authenticate with POST, not GET. Send JSON: email, password, device_name (optional).',
        'web_login_url' => url('/login'),
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::post('/push/register-device', [PushDeviceController::class, 'register'])
    ->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/device/fcm-token', [DeviceController::class, 'storeFcmToken']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/agents', [AgentController::class, 'index']);
        Route::get('/settings/admin-phones', [AdminPhoneSettingsController::class, 'show']);
        Route::put('/settings/admin-phones', [AdminPhoneSettingsController::class, 'update']);
    });

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/session', [ConversationController::class, 'sessionStatus']);
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead']);
    Route::patch('/conversations/{conversation}/assign', [ConversationController::class, 'assign'])
        ->middleware('role:admin');

    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages/text', [MessageController::class, 'sendText']);
    Route::post('/conversations/{conversation}/messages/media', [MessageController::class, 'sendMedia']);
    Route::post('/conversations/{conversation}/messages/template', [TemplateMessageController::class, 'store']);

    Route::get('/messages/poll', [MessageController::class, 'poll']);
});
