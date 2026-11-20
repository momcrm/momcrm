<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractString;

/**
 * What the bank says about the deposit, such as "Direct Credit 062000
 * HARBOUR DENTAL INV-0042".
 */
final class Description extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'description';
    }
}
