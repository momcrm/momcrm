<?php

declare(strict_types=1);

namespace App\Group;

use App\Shared\Traits\Enum;

enum GroupRelationship: string
{
    use Enum;

    case Members = 'members';
    case Projects = 'projects';
}
