<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractDate;

final class ReceivedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'received_at';
    }
}
