<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use Mom\Data\AbstractDate;

final class SentAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'sent_at';
    }
}
