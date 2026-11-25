<?php

declare(strict_types=1);

namespace App\ConversationParticipant\Properties;

use Mom\Data\AbstractDate;

/**
 * When the person last read the conversation; messages after it are unread.
 */
final class LastReadAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_read_at';
    }
}
