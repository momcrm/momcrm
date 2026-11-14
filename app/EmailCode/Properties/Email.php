<?php

declare(strict_types=1);

namespace App\EmailCode\Properties;

use Mom\Data\AbstractString;

final class Email extends AbstractString
{
    public static function getName(): string
    {
        return 'email';
    }
}
