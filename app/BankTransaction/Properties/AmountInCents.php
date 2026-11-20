<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractInteger;

/**
 * The money that came in, in cents of the account's currency.
 */
final class AmountInCents extends AbstractInteger
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'amount_in_cents';
    }
}
