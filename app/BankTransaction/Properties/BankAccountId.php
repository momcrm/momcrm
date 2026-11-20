<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class BankAccountId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'bank_account_id';
    }
}
