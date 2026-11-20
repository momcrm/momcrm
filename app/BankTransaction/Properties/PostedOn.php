<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * The day the money reached the account.
 */
final class PostedOn extends AbstractDateOnly
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'posted_on';
    }
}
