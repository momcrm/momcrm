<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * How a condition compares an attribute with its value. For attributes that
 * hold several values (the groups someone is in), "is" means "includes".
 */
enum AccessOperatorEnum: string
{
    use Enum;
    use EnumOptions;

    case Equals = 'equals';
    case In = 'in';
    case IsFalse = 'is_false';
    case IsTrue = 'is_true';
    case NotEquals = 'not_equals';
    case NotIn = 'not_in';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'is',
            self::In => 'is any of',
            self::IsFalse => 'is no',
            self::IsTrue => 'is yes',
            self::NotEquals => 'is not',
            self::NotIn => 'is none of',
        };
    }

    /**
     * Whether the condition compares against a list of values.
     */
    public function takesList(): bool
    {
        return in_array($this, [self::In, self::NotIn], true);
    }

    /**
     * Whether the condition needs no value at all.
     */
    public function takesNoValue(): bool
    {
        return in_array($this, [self::IsTrue, self::IsFalse], true);
    }
}
