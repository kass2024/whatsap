<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function storeFcmToken(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
        ]);

        $request->user()->update(['fcm_token' => $data['fcm_token']]);

        Log::channel('fcm')->info('FCM device token stored', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
            'token_prefix' => substr($data['fcm_token'], 0, 18).'…',
        ]);

        return response()->json([
            'ok' => true,
            'fcm_token_stored' => true,
        ]);
    }
}
