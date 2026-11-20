<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class PaymentId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'payment_id';
    }
}
