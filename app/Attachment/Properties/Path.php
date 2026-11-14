<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use Mom\Data\AbstractString;

final class Path extends AbstractString
{
    public static function getName(): string
    {
        return 'path';
    }
}
