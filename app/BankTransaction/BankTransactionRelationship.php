<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\Shared\Traits\Enum;

enum BankTransactionRelationship: string
{
    use Enum;

    case BankAccount = 'bankAccount';
    case Customer = 'customer';
}
