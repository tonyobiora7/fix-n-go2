<?php

namespace App\Shared\Enums;

enum SubscriptionStatus: string
{
    case TRIAL_ACTIVE = 'trial_active';
    case PAID_ACTIVE = 'paid_active';
    case EXPIRED = 'expired';
    case GRACE_PERIOD = 'grace_period';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';

    /**
     * Check if subscription is currently active (allows search visibility).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::TRIAL_ACTIVE, self::PAID_ACTIVE]);
    }

    /**
     * Check if subscription allows limited access (grace period).
     */
    public function isGracePeriod(): bool
    {
        return $this === self::GRACE_PERIOD;
    }
}