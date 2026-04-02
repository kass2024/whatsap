<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'phone',
        'customer_name',
        'last_customer_message_at',
        'assigned_to',
        'status',
        'last_message_at',
        'last_message_preview',
        'unread_count',
    ];

    protected function casts(): array
    {
        return [
            'last_customer_message_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
