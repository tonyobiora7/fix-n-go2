<?php

namespace App\Shared\Enums;

enum PaymentMethod: string
{
    case DIRECT = 'direct';
    case PROTECTED = 'protected';

    /**
     * Check if payment uses platform escrow.
     */
    public function isProtected(): bool
    {
        return $this === self::PROTECTED;
    }

    /**
     * Get the platform fee percentage for protected payments.
     */
    public function platformFeePercentage(): float
    {
        return $this === self::PROTECTED ? 7.5 : 0.0;
    }
}