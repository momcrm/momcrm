<?php

declare(strict_types=1);

namespace App\Label;

use App\Shared\Traits\Enum;

enum LabelRelationship: string
{
    use Enum;

    case Tickets = 'tickets';
}
