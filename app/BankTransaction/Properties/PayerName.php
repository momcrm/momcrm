<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractString;

/**
 * Who the bank says paid, when it says so apart from the description.
 */
final class PayerName extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'payer_name';
    }
}
