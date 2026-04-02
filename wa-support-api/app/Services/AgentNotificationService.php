<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AgentNotificationService
{
    public function __construct(
        protected FcmService $fcm
    ) {}

    public function notifyNewCustomerMessage(Conversation $conversation, string $preview): void
    {
        $adminOnly = app(AdminOnlyPhoneService::class);
        $restricted = $adminOnly->isRestricted($conversation->phone);
        $tokens = $this->tokensForConversation($conversation);

        $eligibleUsers = User::query()
            ->whereNotNull('fcm_token')
            ->count();
        $agentsWithToken = User::query()
            ->whereNotNull('fcm_token')
            ->whereIn('role', [UserRole::Agent, UserRole::Admin])
            ->count();

        Log::channel('webhook')->info('wa_support.fcm.notify_new_message', [
            'conversation_id' => $conversation->id,
            'phone' => $conversation->phone,
            'restricted_line' => $restricted,
            'assigned_to' => $conversation->assigned_to,
            'token_targets' => count($tokens),
            'users_with_any_fcm_token' => $eligibleUsers,
            'agents_or_admins_with_fcm_token' => $agentsWithToken,
            'preview_chars' => mb_strlen($preview),
        ]);

        if (count($tokens) === 0) {
            Log::channel('webhook')->warning('wa_support.fcm.no_recipient_tokens', [
                'conversation_id' => $conversation->id,
                'phone' => $conversation->phone,
                'restricted_line' => $restricted,
                'assigned_to' => $conversation->assigned_to,
                'hint' => $restricted
                    ? 'Only admins receive pushes for restricted numbers; ensure an admin logged in on the app once.'
                    : ($conversation->assigned_to
                        ? 'Only the assignee + admins receive pushes for assigned threads.'
                        : 'Agents and admins need a registered device: POST /device/fcm-token after login.'),
            ]);
        }

        $this->fcm->sendToMany(
            $tokens,
            'New WhatsApp message',
            $preview,
            [
                'type' => 'new_message',
                'conversation_id' => (string) $conversation->id,
            ]
        );
    }

    public function notifyConversationAssigned(Conversation $conversation, User $agent): void
    {
        if (! $agent->fcm_token) {
            return;
        }

        $this->fcm->sendToToken(
            $agent->fcm_token,
            'Conversation assigned',
            'You were assigned: '.$conversation->phone,
            [
                'type' => 'assigned',
                'conversation_id' => (string) $conversation->id,
            ]
        );
    }

    /**
     * @return list<string>
     */
    protected function tokensForConversation(Conversation $conversation): array
    {
        $q = User::query()->whereNotNull('fcm_token');

        if (app(AdminOnlyPhoneService::class)->isRestricted($conversation->phone)) {
            $q->where('role', UserRole::Admin);

            return $q->pluck('fcm_token')->filter()->unique()->values()->all();
        }

        if ($conversation->assigned_to) {
            $q->where(function ($w) use ($conversation) {
                $w->where('id', $conversation->assigned_to)
                    ->orWhere('role', UserRole::Admin);
            });
        } else {
            $q->whereIn('role', [UserRole::Agent, UserRole::Admin]);
        }

        return $q->pluck('fcm_token')->filter()->unique()->values()->all();
    }
}
