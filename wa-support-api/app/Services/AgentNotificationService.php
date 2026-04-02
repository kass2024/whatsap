<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;

class AgentNotificationService
{
    public function __construct(
        protected FcmService $fcm
    ) {}

    public function notifyNewCustomerMessage(Conversation $conversation, string $preview): void
    {
        $tokens = $this->tokensForConversation($conversation);

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

        if ($conversation->assigned_to) {
            $q->where(function ($w) use ($conversation) {
                $w->where('id', $conversation->assigned_to)
                    ->orWhere('role', UserRole::Admin);
            });
        } else {
            // Unassigned: notify every agent + admin (previously admin-only, so agents never got alerts).
            $q->whereIn('role', [UserRole::Agent, UserRole::Admin]);
        }

        return $q->pluck('fcm_token')->filter()->unique()->values()->all();
    }
}
