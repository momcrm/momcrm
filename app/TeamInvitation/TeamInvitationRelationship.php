<?php

declare(strict_types=1);

namespace App\TeamInvitation;

use App\Shared\Traits\Enum;

enum TeamInvitationRelationship: string
{
    use Enum;

    case InvitedBy = 'invitedBy';
    case Team = 'team';
}
