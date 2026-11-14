<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Traits\Enum;

/**
 * What a new user told us brings them to MomCRM during onboarding.
 */
enum OnboardingUseCaseEnum: string
{
    use Enum;

    case Agency = 'agency';
    case B2bSales = 'b2b_sales';
    case ManagedServices = 'managed_services';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Running an agency or consultancy',
            self::B2bSales => 'Selling to other businesses',
            self::ManagedServices => 'Managing IT or support for clients',
            self::Other => 'Something else',
        };
    }
}
