<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use Mom\Data\AbstractDate;

final class AcceptedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'accepted_at';
    }
}
