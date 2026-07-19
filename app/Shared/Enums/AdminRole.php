<?php

namespace App\Shared\Enums;

enum AdminRole: string
{
    case SUPPORT = 'support';
    case OPERATIONS = 'operations';
    case SUPER = 'super';

    /**
     * Get permission matrix for each admin role.
     */
    public function permissions(): array
    {
        return match($this) {
            self::SUPPORT => [
                'view_users',
                'view_chats',
                'view_contracts',
                'respond_enquiries',
            ],
            self::OPERATIONS => [
                'view_users',
                'view_chats',
                'view_contracts',
                'respond_enquiries',
                'manage_disputes',
                'manage_subscriptions',
                'manage_payments',
                'suspend_users',
                'reinstate_users',
            ],
            self::SUPER => [
                'view_users',
                'view_chats',
                'view_contracts',
                'respond_enquiries',
                'manage_disputes',
                'manage_subscriptions',
                'manage_payments',
                'suspend_users',
                'reinstate_users',
                'manage_admins',
                'manage_categories',
                'manage_brands',
                'manage_banners',
                'configure_system',
                'view_audit_logs',
            ],
        };
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }
}