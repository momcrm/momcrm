<?php

declare(strict_types=1);

namespace App\Activity;

use App\Shared\Traits\Enum;

enum ActivityRelationship: string
{
    use Enum;

    case Actor = 'actor';
}
