<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // Always use fresh model to avoid stale data
        $this->message = $message->fresh();
    }

    /**
     * Broadcast channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->message->conversation_id);
    }

    /**
     * Event name (Flutter listens to this)
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Payload sent to frontend (MUST match Flutter model)
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_type' => $this->message->sender_type,
            'message_type' => $this->message->message_type,
            'content' => $this->message->content,
            'media_url' => $this->message->media_url,
            'mime_type' => $this->message->mime_type,
            'file_name' => $this->message->file_name,
            'status' => $this->message->status,
            'created_at' => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}