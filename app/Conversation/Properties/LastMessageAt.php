<?php

declare(strict_types=1);

namespace App\Conversation\Properties;

use Mom\Data\AbstractDate;

/**
 * When the latest message was sent, so the newest conversations list first.
 */
final class LastMessageAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_message_at';
    }
}
