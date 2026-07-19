<?php

namespace App\Shared\Enums;

enum ContractStatus: string
{
    case DRAFT = 'draft';
    case PENDING_ACCEPTANCE = 'pending_acceptance';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case GUARANTEED = 'guaranteed';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

    /**
     * Get all status values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if contract is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::CANCELLED, self::CLOSED]);
    }

    /**
     * Check if contract is active and in progress.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::ACTIVE, self::GUARANTEED, self::DISPUTED]);
    }
}