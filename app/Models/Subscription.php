<?php

namespace App\Models;

use App\Shared\Enums\SubscriptionStatus;
use App\Shared\Enums\SubscriptionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'grace_end_date',
        'payment_reference',
        'amount_paid',
    ];

    /**
     * Get the casts for the model.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'grace_end_date' => 'datetime',
            'amount_paid' => 'decimal:2',
            'type' => SubscriptionType::class,
            'status' => SubscriptionStatus::class,
        ];
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::TRIAL_ACTIVE
            || $this->status === SubscriptionStatus::PAID_ACTIVE;
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        if ($this->status !== SubscriptionStatus::GRACE_PERIOD) {
            return false;
        }

        if (!$this->grace_end_date) {
            return false;
        }

        // Convert to Carbon if it's a string
        $graceEndDate = $this->grace_end_date instanceof Carbon 
            ? $this->grace_end_date 
            : Carbon::parse($this->grace_end_date);

        return $graceEndDate->isFuture();
    }

    /**
     * Get days remaining.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->isActive() && !$this->isInGracePeriod()) {
            return 0;
        }

        $endDate = $this->isInGracePeriod() 
            ? $this->grace_end_date 
            : $this->end_date;

        if (!$endDate) {
            return 0;
        }

        // Convert to Carbon if it's a string
        $endDateCarbon = $endDate instanceof Carbon 
            ? $endDate 
            : Carbon::parse($endDate);

        return (int) Carbon::now()->diffInDays($endDateCarbon, false);
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === SubscriptionStatus::EXPIRED) {
            return true;
        }

        if (!$this->end_date) {
            return false;
        }

        // Convert to Carbon if it's a string
        $endDate = $this->end_date instanceof Carbon 
            ? $this->end_date 
            : Carbon::parse($this->end_date);

        return $endDate->isPast();
    }

    /**
     * Activate trial subscription.
     */
    public static function activateTrial(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => SubscriptionType::TRIAL,
            'status' => SubscriptionStatus::TRIAL_ACTIVE,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(SubscriptionType::TRIAL->durationDays()),
        ]);
    }
}