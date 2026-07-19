<?php

namespace App\Shared\Enums;

enum ReviewType: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    /**
     * Get visibility description.
     */
    public function visibilityDescription(): string
    {
        return match($this) {
            self::PUBLIC => 'Visible to all users on public profile',
            self::PRIVATE => 'Visible only to Providers and Dealers',
        };
    }
}