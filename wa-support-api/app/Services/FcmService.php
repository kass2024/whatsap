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
            Log::debug('FCM skipped: missing project_id or service account file.');

            return false;
        }

        $accessToken = $this->accessToken($path);
        if ($accessToken === null) {
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
            ],
        ];

        try {
            $response = Http::timeout(15)
                ->withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('FCM send failed', ['body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM exception', ['e' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  list<string>  $tokens
     */
    public function sendToMany(array $tokens, string $title, string $body, array $data = []): void
    {
        foreach (array_unique(array_filter($tokens)) as $token) {
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
            Log::error('FCM auth failed', ['e' => $e->getMessage()]);

            return null;
        }
    }
}
