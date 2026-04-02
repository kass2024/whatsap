<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'message_type',
        'content',
        'media_disk',
        'media_path',
        'mime_type',
        'file_name',
        'wa_message_id',
        'status',
        'read_at_agent',
        'template_name',
        'template_language',
        'template_components',
        'meta_payload',
    ];

    /**
     * Laravel 10 reads $casts only; the casts() method is Laravel 11+ and was ignored,
     * so meta_payload was sent raw to MySQL (array → "Array to string conversion").
     */
    protected $casts = [
        'read_at_agent' => 'datetime',
        'template_components' => 'array',
        'meta_payload' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function mediaUrl(): ?string
    {
        if (! $this->media_path || ! $this->media_disk) {
            return null;
        }

        return Storage::disk($this->media_disk)->url($this->media_path);
    }
}
