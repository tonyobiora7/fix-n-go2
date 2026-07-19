<?php

namespace App\Shared\Enums;

enum ContractType: string
{
    case JOB = 'job';
    case SUPPLY = 'supply';

    /**
     * Get the creator role for this contract type.
     */
    public function creatorRole(): UserRole
    {
        return match($this) {
            self::JOB => UserRole::CLIENT,
            self::SUPPLY => UserRole::DEALER,
        };
    }

    /**
     * Get the payee role for this contract type.
     */
    public function payeeRole(): UserRole
    {
        return match($this) {
            self::JOB => UserRole::PROVIDER,
            self::SUPPLY => UserRole::DEALER,
        };
    }
}