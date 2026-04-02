<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\AuthorizesConversationAccess;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AdminOnlyPhoneService;
use App\Services\AgentNotificationService;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    use AuthorizesConversationAccess;

    public function __construct(
        protected WhatsAppSessionService $sessions,
        protected WhatsAppCloudService $whatsapp,
        protected AgentNotificationService $notifier,
        protected AdminOnlyPhoneService $adminOnlyPhones
    ) {}

    public function dashboard(): View
    {
        $q = $this->visibleConversationsQuery();
        $total = (clone $q)->count();
        $unreadSum = (clone $q)->sum('unread_count');
        $openCount = (clone $q)->where('status', 'open')->count();
        $recent = (clone $q)->with('assignedAgent:id,name')
            ->orderByDesc('last_message_at')
            ->limit(8)
            ->get();

        $messagesToday = Message::query()->whereDate('created_at', today())->count();
        $restrictedConversationsCount = 0;
        if (auth()->user()->isAdmin()) {
            $restricted = $this->adminOnlyPhones->restrictedPhoneList();
            if ($restricted !== []) {
                $restrictedConversationsCount = Conversation::query()
                    ->whereIn('phone', $restricted)
                    ->count();
            }
        }

        return view('monitoring.dashboard', [
            'total' => $total,
            'unreadSum' => $unreadSum,
            'openCount' => $openCount,
            'recent' => $recent,
            'messagesToday' => $messagesToday,
            'restrictedConversationsCount' => $restrictedConversationsCount,
            'restrictedPhones' => auth()->user()->isAdmin() ? $this->adminOnlyPhones->restrictedPhoneList() : [],
        ]);
    }

    public function index(): View
    {
        $q = $this->visibleConversationsQuery()->with('assignedAgent:id,name,email');
        $conversations = $q->orderByDesc('last_message_at')->orderByDesc('updated_at')->paginate(25);

        $restrictedSet = [];
        if (auth()->user()->isAdmin()) {
            foreach ($this->adminOnlyPhones->restrictedPhoneList() as $p) {
                $restrictedSet[$p] = true;
            }
        }

        return view('monitoring.conversations.index', compact('conversations', 'restrictedSet'));
    }

    public function show(Request $request, Conversation $conversation): View|RedirectResponse
    {
        $this->authorizeConversationAccess(auth()->user(), $conversation);

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at_agent')
            ->update(['read_at_agent' => now()]);
        $conversation->update(['unread_count' => 0]);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->paginate(100);

        $session = $this->sessions->status($conversation);
        $agents = User::query()->where('role', UserRole::Agent)->orderBy('name')->get(['id', 'name', 'email']);

        return view('monitoring.conversations.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'session' => $session,
            'agents' => $agents,
            'isAdminOnlyPhone' => $this->adminOnlyPhones->isRestricted($conversation->phone),
        ]);
    }

    public function sendText(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversationAccess(auth()->user(), $conversation);
        $data = $request->validate(['text' => ['required', 'string', 'max:4096']]);

        if (! $this->sessions->canSendFreeform($conversation)) {
            return back()->withErrors(['text' => __('Session expired. Use a template message.')]);
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
            $waId = $resp['messages'][0]['id'] ?? null;
            $message->update([
                'wa_message_id' => is_string($waId) ? $waId : null,
                'status' => 'sent',
            ]);
        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return back()->withErrors(['text' => __('WhatsApp send failed.')]);
        }

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => mb_substr($data['text'], 0, 500),
        ]);

        return back();
    }

    public function sendMedia(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversationAccess(auth()->user(), $conversation);

        if (! $this->sessions->canSendFreeform($conversation)) {
            return back()->withErrors(['media' => __('Session expired.')]);
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
            $waId = $resp['messages'][0]['id'] ?? null;
            $message->update([
                'wa_message_id' => is_string($waId) ? $waId : null,
                'status' => 'sent',
            ]);
        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return back()->withErrors(['media' => __('WhatsApp media send failed.')]);
        }

        $preview = '['.strtoupper($messageType).'] '.($data['caption'] ?? '');
        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => mb_substr($preview, 0, 500),
        ]);

        return back();
    }

    public function sendTemplate(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversationAccess(auth()->user(), $conversation);
        $data = $request->validate([
            'template_name' => ['required', 'string', 'max:512'],
            'template_language' => ['required', 'string', 'max:16'],
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'message_type' => 'template',
            'content' => $data['template_name'],
            'template_name' => $data['template_name'],
            'template_language' => $data['template_language'],
            'template_components' => [],
            'status' => 'pending',
        ]);

        try {
            $resp = $this->whatsapp->sendTemplate(
                $conversation->phone,
                $data['template_name'],
                $data['template_language'],
                []
            );
            $waId = $resp['messages'][0]['id'] ?? null;
            $message->update([
                'wa_message_id' => is_string($waId) ? $waId : null,
                'status' => 'sent',
            ]);
        } catch (RequestException $e) {
            $message->update(['status' => 'failed']);

            return back()->withErrors(['template' => __('Template send failed.')]);
        }

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => '[Template] '.$data['template_name'],
        ]);

        return back();
    }

    public function assign(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless(auth()->user()->role === UserRole::Admin, 403);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $conversation->assigned_to = $data['assigned_to'];
        $conversation->save();

        if ($data['assigned_to']) {
            $agent = User::query()->find($data['assigned_to']);
            if ($agent) {
                $this->notifier->notifyConversationAssigned($conversation, $agent);
            }
        }

        return back()->with('status', __('Assignment updated.'));
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

    protected function visibleConversationsQuery(): Builder
    {
        $user = auth()->user();
        $q = Conversation::query();
        if ($user->role === UserRole::Agent) {
            $q->where(function ($w) use ($user) {
                $w->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
            $restricted = $this->adminOnlyPhones->restrictedPhoneList();
            if ($restricted !== []) {
                $q->whereNotIn('phone', $restricted);
            }
        }

        return $q;
    }
}
