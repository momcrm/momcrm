<?php

declare(strict_types=1);

namespace App\TeamInvitation\Properties;

use Mom\Data\AbstractDate;

final class ExpiresAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'expires_at';
    }
}
