<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = config('fcm.project_id');
        $path = $this->resolveServiceAccountPath();

        if (!$this->isValidConfig($projectId, $path)) {
            return false;
        }

        $accessToken = $this->accessToken($path);

        if (!$accessToken) {
            Log::channel('fcm')->warning('FCM skipped: failed to get OAuth token');
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => collect($data)->map(fn ($v) => (string) $v)->all(),
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'wa_support_alerts',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        try {
            Log::channel('fcm')->info('FCM request', [
                'token_prefix' => substr($token, 0, 14) . '…',
                'title' => $title,
                'project_id' => $projectId,
            ]);

            $response = Http::timeout(15)
                ->withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::channel('fcm')->error('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::channel('fcm')->info('FCM message sent', [
                'message_id' => $response->json('name'),
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::channel('fcm')->error('FCM exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendToMany(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        Log::channel('fcm')->info('FCM sendToMany', [
            'recipient_count' => count($tokens),
            'title' => $title,
        ]);

        if (empty($tokens)) {
            Log::channel('fcm')->warning('FCM skipped: no recipients');
            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    // =========================
    // 🔧 HELPERS (PRODUCTION SAFE)
    // =========================

    protected function resolveServiceAccountPath(): ?string
    {
        $path = config('fcm.service_account_path');

        // fallback (in case config cache breaks)
        if (!$path) {
            $envPath = $_ENV['FCM_SERVICE_ACCOUNT_PATH'] ?? null;
            if ($envPath) {
                $path = base_path($envPath);
            }
        }

        return is_string($path) ? trim($path) : null;
    }

    protected function isValidConfig(?string $projectId, ?string $path): bool
    {
        $valid = $projectId &&
                 $path &&
                 file_exists($path) &&
                 is_readable($path);

        if (!$valid) {
            Log::channel('fcm')->warning('FCM skipped: configuration error', [
                'project_id' => $projectId,
                'path' => $path,
                'file_exists' => $path ? file_exists($path) : false,
                'readable' => $path ? is_readable($path) : false,
            ]);
        }

        return $valid;
    }

    protected function accessToken(string $path): ?string
    {
        try {
            $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $json
            );

            $handler = HttpHandlerFactory::build();
            $token = $credentials->fetchAuthToken($handler);

            return $token['access_token'] ?? null;

        } catch (\Throwable $e) {
            Log::channel('fcm')->error('FCM auth failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}