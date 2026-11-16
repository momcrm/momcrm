<?php

declare(strict_types=1);

namespace App\Contract;

use App\Shared\Traits\Enum;

enum ContractRelationship: string
{
    use Enum;

    case Customer = 'customer';
}
