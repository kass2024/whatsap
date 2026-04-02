<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppInboundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppInboundService $inbound
    ) {}

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        if (! $this->validSignature($request)) {
            Log::warning('WhatsApp webhook invalid signature');

            return response('Invalid signature', 403);
        }

        $payload = $request->json()->all();
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response()->json(['ok' => true]);
        }

        try {
            $this->inbound->handlePayload($payload);
        } catch (\Throwable $e) {
            Log::error('Webhook processing error', ['e' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    protected function validSignature(Request $request): bool
    {
        $secret = config('whatsapp.app_secret');
        if (! is_string($secret) || $secret === '') {
            return true;
        }

        $sig = $request->header('X-Hub-Signature-256');
        if (! is_string($sig) || ! str_starts_with($sig, 'sha256=')) {
            return false;
        }

        $raw = $request->getContent();
        $expected = 'sha256='.hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $sig);
    }
}
