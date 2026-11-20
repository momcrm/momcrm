<?php

declare(strict_types=1);

namespace App\BankRule;

use App\Shared\Traits\Enum;

enum BankRuleRelationship: string
{
    use Enum;

    case Customer = 'customer';
}
