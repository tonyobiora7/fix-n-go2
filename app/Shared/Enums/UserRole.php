<?php

namespace App\Shared\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case PROVIDER = 'provider';
    case DEALER = 'dealer';
    case ADMIN = 'admin';

    /**
     * Get all role values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if role is searchable (appears in search results).
     */
    public function isSearchable(): bool
    {
        return in_array($this, [self::PROVIDER, self::DEALER]);
    }

    /**
     * Check if role requires BVN verification.
     */
    public function requiresBvnVerification(): bool
    {
        return in_array($this, [self::PROVIDER, self::DEALER]);
    }

    /**
     * Check if role requires phone OTP verification.
     */
    public function requiresPhoneOtp(): bool
    {
        return $this === self::CLIENT;
    }
}