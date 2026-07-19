<?php

namespace App\Shared\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case FUNDED = 'funded';
    case HELD = 'held';
    case FROZEN = 'frozen';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case SPLIT = 'split';
    case CANCELLED = 'cancelled';

    /**
     * Check if payment is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::RELEASED, self::REFUNDED, self::SPLIT, self::CANCELLED]);
    }

    /**
     * Check if funds are currently held.
     */
    public function isHeld(): bool
    {
        return in_array($this, [self::HELD, self::FROZEN]);
    }
}