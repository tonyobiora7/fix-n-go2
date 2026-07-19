<?php

namespace App\Models;

use App\Shared\Enums\ReviewType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'reviewer_id',
        'reviewee_id',
        'type',
        'rating',
        'comment',
        'status',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'type' => ReviewType::class,
        ];
    }

    /**
     * Get the contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the reviewee.
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Check if review is public.
     */
    public function isPublic(): bool
    {
        return $this->type === ReviewType::PUBLIC;
    }

    /**
     * Check if review is private.
     */
    public function isPrivate(): bool
    {
        return $this->type === ReviewType::PRIVATE;
    }
}