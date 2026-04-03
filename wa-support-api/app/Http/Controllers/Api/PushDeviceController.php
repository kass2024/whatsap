<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceFcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushDeviceController extends Controller
{
    /**
     * No auth: registers this device for WhatsApp inbound push alerts (team devices stay notified when logged out).
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'min:80', 'max:4096'],
        ]);

        DeviceFcmToken::query()->updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            ['fcm_token' => $data['fcm_token']]
        );

        Log::channel('fcm')->info('FCM public device token registered', [
            'token_prefix' => substr($data['fcm_token'], 0, 18).'…',
        ]);

        return response()->json(['ok' => true]);
    }
}
