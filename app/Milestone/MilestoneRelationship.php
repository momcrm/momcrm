<?php

declare(strict_types=1);

namespace App\Milestone;

use App\Shared\Traits\Enum;

enum MilestoneRelationship: string
{
    use Enum;

    case Project = 'project';
}
