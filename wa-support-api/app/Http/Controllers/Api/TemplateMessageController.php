<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesConversationAccess;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\Client\RequestException;

class TemplateMessageController extends Controller
{
    use AuthorizesConversationAccess;

    public function __construct(
        protected WhatsAppCloudService $whatsapp
    ) {}

    public function store(\Illuminate\Http\Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:512'],
            'language' => ['required', 'string', 'max:16'],
            'components' => ['nullable', 'array'],
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'message_type' => 'template',
            'content' => $data['name'],
            'template_name' => $data['name'],
            'template_language' => $data['language'],
            'template_components' => $data['components'] ?? [],
            'status' => 'pending',
        ]);

        try {
            $resp = $this->whatsapp->sendTemplate(
                $conversation->phone,
                $data['name'],
                $data['language'],
                $data['components'] ?? []
            );
            $waId = $resp['messages'][0]['id'] ?? null;
            $message->update([
                'wa_message_id' => is_string($waId) ? $waId : null,
                'status' => 'sent',
            ]);
        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Template send failed.',
                'detail' => $e->response?->json() ?? $e->getMessage(),
            ], 502);
        }

        $conversation->last_message_at = now();
        $conversation->last_message_preview = '[Template] '.$data['name'];
        $conversation->save();

        $m = $message->fresh();

        return response()->json([
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_type' => $m->sender_type,
            'message_type' => $m->message_type,
            'template_name' => $m->template_name,
            'template_language' => $m->template_language,
            'status' => $m->status,
            'created_at' => $m->created_at->toIso8601String(),
        ], 201);
    }

    protected function authorizeView(\Illuminate\Http\Request $request, Conversation $conversation): void
    {
        $this->authorizeConversationAccess($request->user(), $conversation);
    }
}
