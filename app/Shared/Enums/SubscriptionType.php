<?php

namespace App\Shared\Enums;

enum SubscriptionType: string
{
    case TRIAL = 'trial';
    case PAID = 'paid';

    /**
     * Get duration in days for each subscription type.
     */
    public function durationDays(): int
    {
        return match($this) {
            self::TRIAL => 7,
            self::PAID => 30, // Default paid period (renewal)
        };
    }

    /**
     * Get duration for first paid subscription.
     */
    public static function firstPaidDurationDays(): int
    {
        return 60;
    }
}