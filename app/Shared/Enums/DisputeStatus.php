<?php

namespace App\Shared\Enums;

enum DisputeStatus: string
{
    case OPEN = 'open';
    case AWAITING_RESPONSE = 'awaiting_response';
    case UNDER_REVIEW = 'under_review';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    /**
     * Check if dispute is still active.
     */
    public function isActive(): bool
    {
        return !in_array($this, [self::RESOLVED, self::CLOSED]);
    }
}