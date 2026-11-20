<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Who a rule is about: the team's own people, or customers' contacts signed
 * in to the customer portal. A rule only ever applies to its own audience, so
 * staff rules never let contacts in and contact rules never touch staff.
 */
enum AccessAudienceEnum: string
{
    use Enum;
    use EnumOptions;

    case Contact = 'contact';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Customer contacts',
            self::Staff => 'Staff',
        };
    }
}
