<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The payment recorded from the line. Deleting the payment clears it, and
 * the line is pending again.
 */
final class PaymentId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'payment_id';
    }
}
