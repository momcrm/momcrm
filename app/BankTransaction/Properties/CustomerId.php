<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class CustomerId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'customer_id';
    }
}
