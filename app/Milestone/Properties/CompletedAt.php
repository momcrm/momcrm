<?php

declare(strict_types=1);

namespace App\Milestone\Properties;

use Mom\Data\AbstractDate;

final class CompletedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'completed_at';
    }
}
