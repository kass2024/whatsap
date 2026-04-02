<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\AuthorizesConversationAccess;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AdminOnlyPhoneService;
use App\Services\WhatsAppSessionService;
use App\Support\Phone;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use AuthorizesConversationAccess;

    public function __construct(
        protected WhatsAppSessionService $sessions,
        protected AdminOnlyPhoneService $adminOnlyPhones
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $q = Conversation::query()->with('assignedAgent:id,name,email');

        if ($user->role === UserRole::Agent) {
            $q->where(function ($w) use ($user) {
                $w->where('assigned_to', $user->id)
                    ->orWhereNull('assigned_to');
            });
            $restricted = $this->adminOnlyPhones->restrictedPhoneList();
            if ($restricted !== []) {
                $q->whereNotIn('phone', $restricted);
            }
        }

        $rows = $q->orderByDesc('last_message_at')->orderByDesc('updated_at')->paginate(30);

        if ($user->isAdmin()) {
            $rows->getCollection()->transform(function (Conversation $c) {
                $c->setAttribute(
                    'is_admin_only',
                    $this->adminOnlyPhones->isRestricted($c->phone)
                );

                return $c;
            });
        }

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:8', 'max:32'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = Phone::normalize($data['phone']);
        if ($phone === '') {
            return response()->json(['message' => 'Invalid phone.'], 422);
        }

        if ($request->user()->role === UserRole::Agent && $this->adminOnlyPhones->isRestricted($phone)) {
            return response()->json(['message' => 'This number is restricted to administrators.'], 403);
        }

        $conversation = Conversation::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'customer_name' => $data['customer_name'] ?? null,
                'status' => 'open',
            ]
        );

        if (! empty($data['customer_name'])) {
            $conversation->customer_name = $data['customer_name'];
            $conversation->save();
        }

        return response()->json($conversation->load('assignedAgent'), 201);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        $conversation->load('assignedAgent');
        if ($request->user()->isAdmin()) {
            $conversation->setAttribute(
                'is_admin_only',
                $this->adminOnlyPhones->isRestricted($conversation->phone)
            );
        }

        return response()->json($conversation);
    }

    public function sessionStatus(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        return response()->json($this->sessions->status($conversation));
    }

    public function assign(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if ($data['assigned_to']) {
            $assignee = User::query()->find($data['assigned_to']);
            if (
                $assignee
                && $this->adminOnlyPhones->isRestricted($conversation->phone)
                && ! $assignee->isAdmin()
            ) {
                return response()->json([
                    'message' => 'This number is admin-only. Assign to an administrator only.',
                ], 422);
            }
        }

        $conversation->assigned_to = $data['assigned_to'];
        $conversation->save();

        if ($data['assigned_to']) {
            $agent = User::query()->find($data['assigned_to']);
            if ($agent) {
                app(\App\Services\AgentNotificationService::class)->notifyConversationAssigned($conversation, $agent);
            }
        }

        return response()->json($conversation->fresh()->load('assignedAgent'));
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $this->authorizeView($request, $conversation);

        \App\Models\Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at_agent')
            ->update(['read_at_agent' => now()]);

        $conversation->unread_count = 0;
        $conversation->save();

        return response()->json(['ok' => true]);
    }

    protected function authorizeView(Request $request, Conversation $conversation): void
    {
        $this->authorizeConversationAccess($request->user(), $conversation);
    }
}
