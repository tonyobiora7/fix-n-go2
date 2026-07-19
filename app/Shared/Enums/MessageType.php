<?php

namespace App\Shared\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case SYSTEM = 'system';

    /**
     * Check if message type is user-generated.
     */
    public function isUserGenerated(): bool
    {
        return in_array($this, [self::TEXT, self::IMAGE]);
    }

    /**
     * Check if message type is system-generated (immutable).
     */
    public function isSystem(): bool
    {
        return $this === self::SYSTEM;
    }
}