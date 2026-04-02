<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppInboundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $expected = (string) config('whatsapp.verify_token');
        $tokenOk = hash_equals($expected, (string) $token);

        Log::channel('webhook')->info('wa_support.webhook.verify', [
            'mode' => $mode,
            'token_length' => is_string($token) ? strlen($token) : null,
            'expected_token_length' => strlen($expected),
            'token_matches' => $tokenOk,
            'challenge_present' => $challenge !== null && $challenge !== '',
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($mode === 'subscribe' && $tokenOk) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('webhook')->warning('wa_support.webhook.verify.denied', [
            'mode' => $mode,
            'reason' => $mode !== 'subscribe' ? 'hub.mode not subscribe' : 'verify token mismatch',
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $correlationId = Str::lower(Str::substr((string) Str::uuid(), 0, 8));
        $rawLen = strlen($request->getContent());
        $sig = $request->header('X-Hub-Signature-256');
        $sigPrefix = is_string($sig) ? Str::substr($sig, 0, 22).'…' : null;
        $secretConfigured = is_string(config('whatsapp.app_secret')) && config('whatsapp.app_secret') !== '';

        Log::channel('webhook')->info('wa_support.webhook.post.received', [
            'correlation_id' => $correlationId,
            'content_length' => $rawLen,
            'signature_prefix' => $sigPrefix,
            'app_secret_configured' => $secretConfigured,
            'signature_check_skipped' => ! $secretConfigured,
            'client_ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        if (! $this->validSignature($request)) {
            Log::warning('WhatsApp webhook invalid signature');
            Log::channel('webhook')->warning('wa_support.webhook.post.signature_invalid', [
                'correlation_id' => $correlationId,
                'content_length' => $rawLen,
                'signature_prefix' => $sigPrefix,
            ]);

            return response('Invalid signature', 403);
        }

        Log::channel('webhook')->info('wa_support.webhook.post.signature_ok', ['correlation_id' => $correlationId]);

        $payload = $request->json()->all();
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            Log::channel('webhook')->info('wa_support.webhook.post.ignored_object', [
                'correlation_id' => $correlationId,
                'object' => $payload['object'] ?? null,
            ]);

            return response()->json(['ok' => true]);
        }

        Log::channel('webhook')->info('wa_support.webhook.post.dispatch_inbound', [
            'correlation_id' => $correlationId,
            'our_phone_number_id' => config('whatsapp.phone_number_id'),
            'entry_count' => is_countable($payload['entry'] ?? null) ? count($payload['entry']) : 0,
        ]);

        try {
            $this->inbound->handlePayload($payload, $correlationId);
            Log::channel('webhook')->info('wa_support.webhook.post.inbound_ok', ['correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            Log::error('Webhook processing error', ['e' => $e->getMessage()]);
            Log::channel('webhook')->error('wa_support.webhook.post.inbound_exception', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
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
