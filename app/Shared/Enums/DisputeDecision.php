<?php

namespace App\Shared\Enums;

enum DisputeDecision: string
{
    case RELEASE = 'release';
    case REFUND = 'refund';
    case SPLIT = 'split';

    /**
     * Get all decision values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}