<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Shared\Traits\Enum;

/**
 * The inbox's tabs: what a notification is about.
 */
enum InboxKindEnum: string
{
    use Enum;

    case Assignment = 'assignment';
    case Comment = 'comment';
    case Mention = 'mention';
    case Update = 'update';
}
