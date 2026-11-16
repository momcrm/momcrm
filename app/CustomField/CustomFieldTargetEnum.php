<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * The kinds of record a team can add its own fields to.
 */
enum CustomFieldTargetEnum: string
{
    use Enum;
    use EnumOptions;

    case Ticket = 'ticket';
    case Customer = 'customer';
    case Contact = 'contact';
    case Project = 'project';

    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Tickets',
            self::Customer => 'Customers',
            self::Contact => 'Contacts',
            self::Project => 'Projects',
        };
    }
}
