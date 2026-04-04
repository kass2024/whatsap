<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesConversationAccess;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent; // ✅ ADDED

class MessageController extends Controller
{
    use AuthorizesConversationAccess;

    public function __construct(
        protected WhatsAppSessionService $sessions,
        protected WhatsAppCloudService $whatsapp
    ) {}

    public function index(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->paginate(50);

        $messages->getCollection()->transform(fn (Message $m) => $this->transformMessage($m));

        return response()->json($messages);
    }

    public function poll(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $conversation = Conversation::query()->findOrFail($data['conversation_id']);
        $this->authorizeView($request, $conversation);

        $q = Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id');

        if (isset($data['after_id']) && $data['after_id'] > 0) {
            $q->where('id', '>', $data['after_id']);
        }

        $list = $q->limit(100)->get()->map(fn (Message $m) => $this->transformMessage($m));

        return response()->json(['messages' => $list]);
    }

    public function sendText(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:4096'],
        ]);

        if (! $this->sessions->canSendFreeform($conversation)) {
            return response()->json([
                'message' => 'Session expired or not started. Use a WhatsApp template message.',
                'session' => $this->sessions->status($conversation),
            ], 422);
        }

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'message_type' => 'text',
            'content' => $data['text'],
            'status' => 'pending',
        ]);

        try {
            $resp = $this->whatsapp->sendText($conversation->phone, $data['text']);
            $waId = $this->extractWaMessageId($resp);

            $message->update([
                'wa_message_id' => $waId,
                'status' => 'sent',
            ]);

            // ✅ REAL-TIME BROADCAST (ONLY AFTER SUCCESS)
            broadcast(new MessageSent($message->fresh()))->toOthers();

        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return response()->json([
                'message' => 'WhatsApp send failed.',
                'detail' => $e->response?->json() ?? $e->getMessage(),
            ], 502);
        }

        $this->touchConversationPreview($conversation, $data['text']);

        return response()->json($this->transformMessage($message->fresh()), 201);
    }

    public function sendMedia(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        if (! $this->sessions->canSendFreeform($conversation)) {
            return response()->json([
                'message' => 'Session expired. Use a template message.',
                'session' => $this->sessions->status($conversation),
            ], 422);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:25600'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ]);

        $uploaded = $data['file'];
        $mime = $uploaded->getMimeType() ?? 'application/octet-stream';
        $disk = config('whatsapp.media_disk');
        $dir = trim(config('whatsapp.media_directory'), '/').'/out';
        $path = $uploaded->store($dir, $disk);

        $messageType = $this->guessMessageType($mime);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'message_type' => $messageType,
            'content' => $data['caption'] ?? null,
            'media_disk' => $disk,
            'media_path' => $path,
            'mime_type' => $mime,
            'file_name' => $uploaded->getClientOriginalName(),
            'status' => 'pending',
        ]);

        try {
            $resp = $this->whatsapp->sendMediaFromStorage(
                $conversation->phone,
                $disk,
                $path,
                $data['caption'] ?? null
            );

            $waId = $this->extractWaMessageId($resp);

            $message->update([
                'wa_message_id' => $waId,
                'status' => 'sent',
            ]);

            // ✅ REAL-TIME BROADCAST
            broadcast(new MessageSent($message->fresh()))->toOthers();

        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return response()->json([
                'message' => 'WhatsApp media send failed.',
                'detail' => $e->response?->json() ?? $e->getMessage(),
            ], 502);
        }

        $preview = '['.strtoupper($messageType).'] '.($data['caption'] ?? '');
        $this->touchConversationPreview($conversation, $preview);

        return response()->json($this->transformMessage($message->fresh()), 201);
    }

    protected function guessMessageType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default => 'document',
        };
    }

    protected function extractWaMessageId(array $resp): ?string
    {
        $messages = $resp['messages'] ?? [];
        return $messages[0]['id'] ?? null;
    }

    protected function touchConversationPreview(Conversation $conversation, string $preview): void
    {
        $conversation->last_message_at = now();
        $conversation->last_message_preview = mb_substr($preview, 0, 500);
        $conversation->save();
    }

    protected function transformMessage(Message $m): array
    {
        $mediaUrl = $this->absolutePublicMediaUrl($m->media_disk, $m->media_path);

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_type' => $m->sender_type,
            'message_type' => $m->message_type,
            'content' => $m->content,
            'media_url' => $mediaUrl,
            'mime_type' => $m->mime_type,
            'file_name' => $m->file_name,
            'status' => $m->status,
            'read_at_agent' => $m->read_at_agent?->toIso8601String(),
            'template_name' => $m->template_name,
            'template_language' => $m->template_language,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }

    protected function absolutePublicMediaUrl(?string $disk, ?string $path): ?string
    {
        if (! is_string($disk) || $disk === '' || ! is_string($path) || $path === '') {
            return null;
        }

        try {
            $relative = Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            return null;
        }

        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($relative, '/');
    }

    protected function authorizeView(Request $request, Conversation $conversation): void
    {
        $this->authorizeConversationAccess($request->user(), $conversation);
    }
}