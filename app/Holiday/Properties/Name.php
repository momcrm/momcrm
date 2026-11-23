<?php

declare(strict_types=1);

namespace App\Holiday\Properties;

use Mom\Data\AbstractString;

final class Name extends AbstractString
{
    public const int MAX = 100;

    public static function getName(): string
    {
        return 'name';
    }
}
