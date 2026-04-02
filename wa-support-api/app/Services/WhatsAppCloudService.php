<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppCloudService
{
    public function graphUrl(string $path): string
    {
        $version = config('whatsapp.graph_version');
        $path = ltrim($path, '/');

        return "https://graph.facebook.com/{$version}/{$path}";
    }

    public function sendText(string $phoneDigits, string $text): array
    {
        $phoneDigits = preg_replace('/\D+/', '', $phoneDigits) ?? '';

        return $this->postMessages([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneDigits,
            'type' => 'text',
            'text' => ['preview_url' => true, 'body' => $text],
        ]);
    }

    public function sendMedia(
        string $phoneDigits,
        string $type,
        string $linkOrId,
        ?string $caption = null,
        bool $linkIsUrl = true
    ): array {
        $phoneDigits = preg_replace('/\D+/', '', $phoneDigits) ?? '';
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneDigits,
            'type' => $type,
        ];
        $key = $linkIsUrl ? 'link' : 'id';
        $payload[$type] = array_filter([
            $key => $linkOrId,
            'caption' => $caption,
        ]);

        return $this->postMessages($payload);
    }

    /**
     * Upload binary to WhatsApp media API and return media id.
     */
    public function uploadMediaFile(string $absolutePath, string $mimeType): string
    {
        $phoneId = config('whatsapp.phone_number_id');
        $token = config('whatsapp.access_token');
        $url = $this->graphUrl("{$phoneId}/media");

        $waType = $this->mimeToWaType($mimeType) ?? 'document';

        $response = Http::timeout((int) config('whatsapp.timeout'))
            ->withToken($token)
            ->attach('file', file_get_contents($absolutePath), basename($absolutePath))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'type' => $waType,
            ]);

        $response->throw();
        $id = $response->json('id');
        if (! is_string($id) || $id === '') {
            throw new \RuntimeException('WhatsApp media upload did not return id.');
        }

        return $id;
    }

    public function sendMediaFromStorage(
        string $phoneDigits,
        string $disk,
        string $path,
        ?string $caption = null
    ): array {
        $full = Storage::disk($disk)->path($path);
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $mediaId = $this->uploadMediaFile($full, $mime);
        $waType = $this->mimeToWaType($mime) ?? 'document';

        return $this->sendMedia($phoneDigits, $waType, $mediaId, $caption, false);
    }

    public function sendTemplate(
        string $phoneDigits,
        string $name,
        string $languageCode,
        array $components = []
    ): array {
        $phoneDigits = preg_replace('/\D+/', '', $phoneDigits) ?? '';

        $body = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneDigits,
            'type' => 'template',
            'template' => array_filter([
                'name' => $name,
                'language' => ['code' => $languageCode],
                'components' => $components ?: null,
            ]),
        ];

        return $this->postMessages($body);
    }

    public function getMediaUrl(string $mediaId): string
    {
        $token = config('whatsapp.access_token');
        $response = Http::timeout((int) config('whatsapp.timeout'))
            ->withToken($token)
            ->get($this->graphUrl($mediaId));

        $response->throw();
        $url = $response->json('url');
        if (! is_string($url) || $url === '') {
            throw new \RuntimeException('Media metadata missing url.');
        }

        return $url;
    }

    public function downloadMediaToDisk(string $mediaId, string $extension, ?string $mimeHint = null): array
    {
        $url = $this->getMediaUrl($mediaId);
        $token = config('whatsapp.access_token');
        $binary = Http::timeout((int) config('whatsapp.timeout'))
            ->withToken($token)
            ->get($url)
            ->throw()
            ->body();

        $disk = config('whatsapp.media_disk');
        $dir = trim(config('whatsapp.media_directory'), '/');
        $name = $dir.'/in/'.uniqid('', true).'.'.$extension;
        Storage::disk($disk)->put($name, $binary);

        $mime = $mimeHint ?? Storage::disk($disk)->mimeType($name);

        return ['disk' => $disk, 'path' => $name, 'mime' => $mime];
    }

    protected function postMessages(array $body): array
    {
        $phoneId = config('whatsapp.phone_number_id');
        $token = config('whatsapp.access_token');
        $url = $this->graphUrl("{$phoneId}/messages");

        try {
            $response = Http::timeout((int) config('whatsapp.timeout'))
                ->withToken($token)
                ->acceptJson()
                ->post($url, $body);

            $response->throw();

            return $response->json() ?? [];
        } catch (RequestException $e) {
            Log::error('WhatsApp send failed', [
                'body' => $body,
                'error' => $e->response?->body(),
            ]);
            throw $e;
        }
    }

    protected function mimeToWaType(string $mime): ?string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default => 'document',
        };
    }
}
