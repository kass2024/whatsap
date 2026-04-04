<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // Always send fresh data (important for relations)
        $this->message = $message->load(['conversation', 'sender']);
    }

    /**
     * Broadcast channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->message->conversation_id);
    }

    /**
     * Event name (frontend listens to this)
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Data sent to frontend (VERY IMPORTANT)
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'file_url' => $this->message->file_url,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}