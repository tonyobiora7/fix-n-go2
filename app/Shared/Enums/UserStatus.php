<?php

namespace App\Shared\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
    case PENDING_VERIFICATION = 'pending_verification';
    case AWAITING_BVN = 'awaiting_bvn';
    case VERIFICATION_FAILED = 'verification_failed';

    /**
     * Get all status values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if account is active and usable.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if account can search or be searched.
     */
    public function isSearchable(): bool
    {
        return $this === self::ACTIVE;
    }
}