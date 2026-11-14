<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Mom\Data\AbstractDate;

final class ArchivedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'archived_at';
    }
}
