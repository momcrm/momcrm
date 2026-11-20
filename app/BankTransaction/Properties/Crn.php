<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractString;

/**
 * The customer reference number a BPAY payment was made with.
 */
final class Crn extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'crn';
    }
}
