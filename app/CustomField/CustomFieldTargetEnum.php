<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomObject\StandardObjectEnum;
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
            self::Record => 'Custom objects',
            default => $this->standardObject()?->label() ?? ucfirst($this->value),
        };
    }

    /**
     * The built-in kind of record the fields are on; null for custom objects.
     */
    public function standardObject(): ?StandardObjectEnum
    {
        return StandardObjectEnum::tryFrom($this->value);
    }
}
