<?php

declare(strict_types=1);

namespace App\Message;

use App\Shared\Traits\Enum;

enum MessageRelationship: string
{
    use Enum;

    case Author = 'author';
}
