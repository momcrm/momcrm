<?php

declare(strict_types=1);

namespace App\User\Properties;

use Mom\Data\AbstractDate;

final class OnboardedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'onboarded_at';
    }
}
