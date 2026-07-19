<?php

namespace App\Models;

use App\Shared\Enums\MessageType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chat_id',
        'sender_id',
        'type',
        'content',
        'image_url',
        'image_size',
        'is_read',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'is_read' => 'boolean',
        'image_size' => 'integer',
        'read_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'read_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the chat that owns the message.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if the message is a system message.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->type === MessageType::SYSTEM->value || $this->sender_id === null;
    }

    /**
     * Check if the message is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === MessageType::IMAGE->value;
    }

    /**
     * Check if the message is text.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return $this->type === MessageType::TEXT->value;
    }

    /**
     * Get the formatted content for display.
     * For system messages, returns the content as-is.
     * For images, returns the image URL.
     *
     * @return string
     */
    public function getDisplayContent(): string
    {
        if ($this->isImage()) {
            return $this->image_url ?? $this->content;
        }

        return $this->content;
    }

    /**
     * Mark the message as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}