<?php

declare(strict_types=1);

namespace App\User\Properties;

use Mom\Data\AbstractDate;

final class GettingStartedDismissedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'getting_started_dismissed_at';
    }
}
