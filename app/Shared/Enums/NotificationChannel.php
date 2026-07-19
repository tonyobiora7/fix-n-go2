<?php

namespace App\Shared\Enums;

enum NotificationChannel: string
{
    case PUSH = 'push';
    case EMAIL = 'email';
    case IN_APP = 'in_app';

    /**
     * Get all channel values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}