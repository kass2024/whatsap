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
        $path = config('fcm.service_account_path');

        if (! $projectId || ! $path || ! is_readable($path)) {
            $ctx = [
                'project_id_set' => is_string($projectId) && $projectId !== '',
                'service_account_path_set' => is_string($path) && $path !== '',
                'service_account_readable' => is_string($path) && is_readable($path),
                'env_FCM_PROJECT_ID' => env('FCM_PROJECT_ID') !== null && env('FCM_PROJECT_ID') !== '',
                'env_FCM_SERVICE_ACCOUNT_PATH' => env('FCM_SERVICE_ACCOUNT_PATH') !== null && (string) env('FCM_SERVICE_ACCOUNT_PATH') !== '',
            ];
            Log::channel('fcm')->warning('FCM skipped: configuration', $ctx);
            Log::channel('webhook')->warning('wa_support.fcm.skipped_config', $ctx);

            return false;
        }

        $accessToken = $this->accessToken($path);
        if ($accessToken === null) {
            Log::channel('fcm')->warning('FCM skipped: no OAuth access token (check service account JSON).');
            Log::channel('webhook')->warning('wa_support.fcm.skipped_auth');

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
                'project_id' => $projectId,
                'token_prefix' => substr($token, 0, 14).'…',
                'title' => $title,
                'data_keys' => array_keys($data),
            ]);

            $response = Http::timeout(15)
                ->withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload);

            if (! $response->successful()) {
                $err = [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token_prefix' => substr($token, 0, 14).'…',
                ];
                Log::channel('fcm')->warning('FCM send failed', $err);
                Log::channel('webhook')->warning('wa_support.fcm.send_failed', $err);

                return false;
            }

            $accepted = [
                'token_prefix' => substr($token, 0, 14).'…',
                'message_id' => $response->json('name'),
            ];
            Log::channel('fcm')->info('FCM message accepted', $accepted);
            Log::channel('webhook')->info('wa_support.fcm.message_accepted', $accepted);

            return true;
        } catch (\Throwable $e) {
            Log::channel('fcm')->error('FCM exception', ['e' => $e->getMessage()]);
            Log::channel('webhook')->error('wa_support.fcm.exception', ['e' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  list<string>  $tokens
     */
    public function sendToMany(array $tokens, string $title, string $body, array $data = []): void
    {
        $unique = array_values(array_unique(array_filter($tokens)));
        Log::channel('fcm')->info('FCM sendToMany', [
            'recipient_count' => count($unique),
            'title' => $title,
            'data_keys' => array_keys($data),
        ]);

        foreach ($unique as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    protected function accessToken(string $serviceAccountPath): ?string
    {
        try {
            $json = json_decode((string) file_get_contents($serviceAccountPath), true, 512, JSON_THROW_ON_ERROR);
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $json
            );
            $handler = HttpHandlerFactory::build();
            $token = $credentials->fetchAuthToken($handler);

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::channel('fcm')->error('FCM auth failed', ['e' => $e->getMessage()]);
            Log::channel('webhook')->error('wa_support.fcm.auth_failed', ['e' => $e->getMessage()]);

            return null;
        }
    }
}
