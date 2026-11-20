<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use Mom\Data\AbstractString;

/**
 * What tells the line apart from every other in its account, so the same
 * statement can be imported twice: the bank's own id when the file has one,
 * otherwise a fingerprint of the row.
 */
final class ExternalId extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'external_id';
    }
}
