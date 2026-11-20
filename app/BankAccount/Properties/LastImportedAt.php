<?php

declare(strict_types=1);

namespace App\BankAccount\Properties;

use Mom\Data\AbstractDate;

final class LastImportedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_imported_at';
    }
}
