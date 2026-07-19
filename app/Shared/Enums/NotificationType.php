<?php

namespace App\Shared\Enums;

enum NotificationType: string
{
    case REGISTRATION = 'registration';
    case VERIFICATION = 'verification';
    case SUBSCRIPTION = 'subscription';
    case CHAT = 'chat';
    case CONTRACT = 'contract';
    case PAYMENT = 'payment';
    case GUARANTEE = 'guarantee';
    case DISPUTE = 'dispute';
    case REVIEW = 'review';

    /**
     * Get all type values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}