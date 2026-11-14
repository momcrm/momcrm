<?php

declare(strict_types=1);

namespace App\EmailCode\Properties;

use Mom\Data\AbstractString;

final class CodeHash extends AbstractString
{
    public static bool $includeInArray = false;

    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'code_hash';
    }
}
