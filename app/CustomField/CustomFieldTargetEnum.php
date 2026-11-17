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
    case Record = 'record';

    /**
     * The kinds of record built into the app. Records of custom objects are
     * the other one, where a field also says which object it is on.
     *
     * @return list<self>
     */
    public static function builtIn(): array
    {
        return [self::Ticket, self::Customer, self::Contact, self::Project];
    }

    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Tickets',
            self::Customer => 'Customers',
            self::Contact => 'Contacts',
            self::Project => 'Projects',
            self::Record => 'Custom objects',
        };
    }
}
