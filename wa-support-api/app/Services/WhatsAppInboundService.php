<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppInboundService
{
    public function __construct(
        protected WhatsAppCloudService $cloud,
        protected AgentNotificationService $notifier
    ) {}

    public function handlePayload(array $payload): void
    {
        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }

                $this->processStatuses($value['statuses'] ?? []);
                $this->processMessages($value);
            }
        }
    }

    protected function processStatuses(mixed $statuses): void
    {
        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $st) {
            if (! is_array($st)) {
                continue;
            }
            $waId = $st['id'] ?? null;
            $status = $st['status'] ?? null;
            if (! is_string($waId) || ! is_string($status)) {
                continue;
            }

            $map = [
                'sent' => 'sent',
                'delivered' => 'delivered',
                'read' => 'read',
                'failed' => 'failed',
            ];
            $internal = $map[$status] ?? null;
            if ($internal) {
                Message::query()->where('wa_message_id', $waId)->update(['status' => $internal]);
            }
        }
    }

    protected function processMessages(array $value): void
    {
        $messages = $value['messages'] ?? [];
        if (! is_array($messages)) {
            return;
        }

        $contacts = $value['contacts'] ?? [];
        $nameByPhone = [];
        if (is_array($contacts)) {
            foreach ($contacts as $c) {
                if (! is_array($c)) {
                    continue;
                }
                $waId = isset($c['wa_id']) ? Phone::normalize((string) $c['wa_id']) : '';
                $name = $c['profile']['name'] ?? null;
                if ($waId !== '' && is_string($name)) {
                    $nameByPhone[$waId] = $name;
                }
            }
        }

        foreach ($messages as $msg) {
            if (! is_array($msg)) {
                continue;
            }
            try {
                $this->processOneMessage($msg, $nameByPhone);
            } catch (\Throwable $e) {
                Log::error('Inbound message failed', ['e' => $e->getMessage(), 'msg' => $msg]);
            }
        }
    }

    protected function processOneMessage(array $msg, array $nameByPhone): void
    {
        $from = Phone::normalize($msg['from'] ?? '');
        if ($from === '') {
            return;
        }

        $waMessageId = $msg['id'] ?? null;
        if (is_string($waMessageId) && Message::query()->where('wa_message_id', $waMessageId)->exists()) {
            return;
        }

        $type = $msg['type'] ?? 'unknown';

        DB::transaction(function () use ($from, $waMessageId, $type, $msg, $nameByPhone) {
            $conversation = Conversation::query()->firstOrCreate(
                ['phone' => $from],
                ['customer_name' => $nameByPhone[$from] ?? null, 'status' => 'open']
            );

            if (isset($nameByPhone[$from]) && $conversation->customer_name === null) {
                $conversation->customer_name = $nameByPhone[$from];
                $conversation->save();
            }

            $conversation->last_customer_message_at = now();
            $conversation->last_message_at = now();

            $preview = '';
            $messageType = 'text';
            $content = null;
            $mediaDisk = null;
            $mediaPath = null;
            $mime = null;
            $fileName = null;

            if ($type === 'text') {
                $content = $msg['text']['body'] ?? '';
                $preview = mb_substr((string) $content, 0, 200);
                $messageType = 'text';
            } elseif (in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
                $media = $msg[$type] ?? [];
                $mediaId = is_array($media) ? ($media['id'] ?? null) : null;
                $caption = is_array($media) ? ($media['caption'] ?? null) : null;
                $mime = is_array($media) ? ($media['mime_type'] ?? null) : null;
                $fileName = is_array($media) ? ($media['filename'] ?? null) : null;

                $messageType = $type === 'sticker' ? 'image' : $type;
                $content = is_string($caption) ? $caption : null;
                $preview = '['.strtoupper($messageType).'] '.($content ?? '');

                if (is_string($mediaId)) {
                    $ext = $this->guessExtension($messageType, $mime, $fileName);
                    try {
                        $stored = $this->cloud->downloadMediaToDisk($mediaId, $ext, is_string($mime) ? $mime : null);
                        $mediaDisk = $stored['disk'];
                        $mediaPath = $stored['path'];
                        $mime = $stored['mime'] ?? $mime;
                    } catch (\Throwable $e) {
                        Log::warning('Media download failed', ['id' => $mediaId, 'e' => $e->getMessage()]);
                        $preview = '[Media download failed] '.$preview;
                    }
                }
            } elseif ($type === 'button') {
                $content = $msg['button']['text'] ?? json_encode($msg);
                $messageType = 'text';
                $preview = (string) $content;
            } elseif ($type === 'interactive') {
                $content = json_encode($msg['interactive'] ?? []);
                $messageType = 'text';
                $preview = '[Interactive]';
            } else {
                $content = json_encode($msg);
                $messageType = 'text';
                $preview = '['.$type.']';
            }

            $conversation->last_message_preview = $preview;
            $conversation->unread_count = ($conversation->unread_count ?? 0) + 1;
            $conversation->save();

            Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'customer',
                'message_type' => $messageType,
                'content' => $content,
                'media_disk' => $mediaDisk,
                'media_path' => $mediaPath,
                'mime_type' => $mime,
                'file_name' => $fileName,
                'wa_message_id' => is_string($waMessageId) ? $waMessageId : null,
                'status' => 'delivered',
                'meta_payload' => $msg,
            ]);

            $conversation->refresh();
            $this->notifier->notifyNewCustomerMessage($conversation, $preview);
        });
    }

    protected function guessExtension(string $messageType, ?string $mime, ?string $fileName): string
    {
        if (is_string($fileName) && str_contains($fileName, '.')) {
            return pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin';
        }

        if (is_string($mime)) {
            $map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'audio/ogg' => 'ogg',
                'audio/mpeg' => 'mp3',
                'audio/mp4' => 'm4a',
                'video/mp4' => 'mp4',
                'application/pdf' => 'pdf',
            ];

            return $map[$mime] ?? 'bin';
        }

        return match ($messageType) {
            'image' => 'jpg',
            'audio' => 'ogg',
            'video' => 'mp4',
            'document' => 'pdf',
            default => 'bin',
        };
    }
}
