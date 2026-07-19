<?php

namespace App\Models;

use App\Shared\Enums\MessageType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'creator_id',
        'recipient_id',
        'status',
        'contract_created',
        'contract_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contract_created' => 'boolean',
        'status' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the creator of the chat.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the recipient of the chat.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the messages for the chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    /**
     * Get the contract associated with the chat.
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'chat_id');
    }

    /**
     * Get the other participant in the chat.
     *
     * @param int|string $userId
     * @return User|null
     */
    public function getOtherParticipant($userId): ?User
    {
        if ($this->creator_id === $userId) {
            return $this->recipient;
        }

        if ($this->recipient_id === $userId) {
            return $this->creator;
        }

        return null;
    }

    /**
     * Check if a user is a participant in this chat.
     *
     * @param int|string $userId
     * @return bool
     */
    public function isParticipant($userId): bool
    {
        return $this->creator_id === $userId || $this->recipient_id === $userId;
    }

    /**
     * Get the last message in the chat.
     *
     * @return Message|null
     */
    public function getLastMessageAttribute(): ?Message
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Get the unread message count for a user.
     *
     * @param int|string $userId
     * @return int
     */
    public function getUnreadCountForUser($userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark all messages as read for a user.
     *
     * @param int|string $userId
     * @return int Number of messages marked as read
     */
    public function markAsReadForUser($userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Check if a contract can be created from this chat.
     *
     * @return bool
     */
    public function canCreateContract(): bool
    {
        return !$this->contract_created && $this->status === 'active';
    }

    /**
     * Mark that a contract has been created from this chat.
     *
     * @param string $contractId
     * @return void
     */
    public function markContractCreated(string $contractId): void
    {
        $this->update([
            'contract_created' => true,
            'contract_id' => $contractId,
        ]);
    }

    /**
     * Create a system message in the chat.
     *
     * @param string $content
     * @return Message
     */
    public function createSystemMessage(string $content): Message
    {
        return $this->messages()->create([
            'sender_id' => null,
            'type' => MessageType::SYSTEM->value,
            'content' => $content,
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}