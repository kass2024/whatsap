<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AdminOnlyPhoneService;

trait AuthorizesConversationAccess
{
    protected function authorizeConversationAccess(User $user, Conversation $conversation): void
    {
        if ($user->role === UserRole::Admin) {
            return;
        }

        if (app(AdminOnlyPhoneService::class)->isRestricted($conversation->phone)) {
            abort(403);
        }

        if ($conversation->assigned_to === null) {
            return;
        }

        if ((int) $conversation->assigned_to === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
