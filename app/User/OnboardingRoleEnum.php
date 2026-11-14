<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Traits\Enum;

/**
 * The role a new user told us they have during onboarding.
 */
enum OnboardingRoleEnum: string
{
    use Enum;

    case Finance = 'finance';
    case Manager = 'manager';
    case Owner = 'owner';
    case Sales = 'sales';
    case Technician = 'technician';

    public function label(): string
    {
        return match ($this) {
            self::Finance => 'Finance or admin',
            self::Manager => 'Manager',
            self::Owner => 'Owner or founder',
            self::Sales => 'Sales or account management',
            self::Technician => 'Technician or support',
        };
    }
}
