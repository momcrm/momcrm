<?php

declare(strict_types=1);

namespace App\User\Properties;

use Mom\Data\AbstractDate;

final class EmailVerifiedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'email_verified_at';
    }
}
