<?php

declare(strict_types=1);

namespace App\Shift;

use App\Shared\Traits\Enum;

enum ShiftRelationship: string
{
    use Enum;

    case Breaks = 'breaks';
    case User = 'user';
}
