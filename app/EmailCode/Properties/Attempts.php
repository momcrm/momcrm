<?php

declare(strict_types=1);

namespace App\EmailCode\Properties;

use Mom\Data\AbstractInteger;

final class Attempts extends AbstractInteger
{
    public static function getName(): string
    {
        return 'attempts';
    }
}
