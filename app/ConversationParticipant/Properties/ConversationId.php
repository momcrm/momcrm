<?php

declare(strict_types=1);

namespace App\ConversationParticipant\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ConversationId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'conversation_id';
    }
}
