<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class LiveChatService
{
    /**
     * Broadcast chat list updates to all connected clients
     */
    public function broadcastChatUpdate(array $conversationIds): void
    {
        try {
            Broadcast::channel('chat-updates')->json([
                'type' => 'chat_list_update',
                'conversation_ids' => $conversationIds,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast chat update: ' . $e->getMessage());
        }
    }

    /**
     * Get active conversation IDs for live updates
     */
    public function getActiveConversationIds(): array
    {
        return Conversation::query()
            ->where('updated_at', '>', now()->subMinutes(30))
            ->pluck('id')
            ->toArray();
    }

    /**
     * Search conversations with real-time filtering
     */
    public function searchConversations(string $searchTerm, int $userId): array
    {
        $query = Conversation::query()
            ->where(function ($query) use ($searchTerm) {
                $query->where('customer_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            });

        // Apply user access restrictions
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', $userId);
        }

        return $query->with(['assignedAgent:id,name'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get()
            ->toArray();
    }
}
