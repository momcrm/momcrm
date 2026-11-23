<?php

declare(strict_types=1);

namespace App\LeaveRequest\Properties;

use Mom\Data\AbstractString;

final class ReviewNote extends AbstractString
{
    public const int MAX = 500;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'review_note';
    }
}
