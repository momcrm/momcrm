<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use Mom\Data\AbstractString;

final class Note extends AbstractString
{
    public static function getName(): string
    {
        return 'note';
    }
}
