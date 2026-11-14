<?php

declare(strict_types=1);

namespace App\Project;

use App\Shared\Traits\Enum;

enum ProjectRelationship: string
{
    use Enum;

    case Customer = 'customer';
    case Lead = 'lead';
    case Group = 'group';
    case Members = 'members';
    case Milestones = 'milestones';
    case Tickets = 'tickets';
}
