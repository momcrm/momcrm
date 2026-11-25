<?php

declare(strict_types=1);

namespace App\Conversation;

use App\Shared\Traits\Enum;

enum ConversationRelationship: string
{
    use Enum;

    case Participants = 'participants';
}
