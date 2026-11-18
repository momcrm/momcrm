<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

final class Subject extends AbstractString
{
    public static function getName(): string
    {
        return 'subject';
    }
}
