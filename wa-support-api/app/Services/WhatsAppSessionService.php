<?php

namespace App\Services;

use App\Models\Conversation;
use Carbon\Carbon;

class WhatsAppSessionService
{
    public const SESSION_HOURS = 24;

    public function isSessionActive(Conversation $conversation): bool
    {
        if ($conversation->last_customer_message_at === null) {
            return false;
        }

        return $conversation->last_customer_message_at->greaterThan(
            Carbon::now()->subHours(self::SESSION_HOURS)
        );
    }

    public function canSendFreeform(Conversation $conversation): bool
    {
        return $this->isSessionActive($conversation);
    }

    /**
     * @return array{active: bool, expires_at: ?string, reason: string}
     */
    public function status(Conversation $conversation): array
    {
        if ($conversation->last_customer_message_at === null) {
            return [
                'active' => false,
                'expires_at' => null,
                'reason' => 'no_customer_message_yet',
            ];
        }

        $expiresAt = $conversation->last_customer_message_at->copy()->addHours(self::SESSION_HOURS);

        if ($expiresAt->isFuture()) {
            return [
                'active' => true,
                'expires_at' => $expiresAt->toIso8601String(),
                'reason' => 'within_24h_window',
            ];
        }

        return [
            'active' => false,
            'expires_at' => $expiresAt->toIso8601String(),
            'reason' => 'session_expired',
        ];
    }
}
