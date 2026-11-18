<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractString;

final class DisplayName extends AbstractString
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'display_name';
    }
}
