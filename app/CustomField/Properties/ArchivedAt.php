<?php

declare(strict_types=1);

namespace App\CustomField\Properties;

use Mom\Data\AbstractDate;

final class ArchivedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'archived_at';
    }
}
