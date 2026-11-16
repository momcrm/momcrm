<?php

declare(strict_types=1);

namespace App\Contract\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class CustomerId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'customer_id';
    }
}
