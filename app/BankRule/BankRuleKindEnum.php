<?php

declare(strict_types=1);

namespace App\BankRule;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What a bank feed rule does with a deposit whose description contains its
 * text: say which customer paid it, or hide it as not a customer's payment.
 */
enum BankRuleKindEnum: string
{
    use Enum;
    use EnumOptions;

    case Match = 'match';
    case Hide = 'hide';

    public function label(): string
    {
        return match ($this) {
            self::Match => 'Is from a customer',
            self::Hide => 'Hide it',
        };
    }
}
