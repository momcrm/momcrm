<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Shared\Traits\Enum;

/**
 * What a notification is about, which is also what someone can choose to be
 * emailed about. A direct message is not listed among the notifications; it
 * is its own tab in the inbox.
 */
enum InboxKindEnum: string
{
    use Enum;

    case NewTicket = 'new_ticket';
    case Assignment = 'assignment';
    case Comment = 'comment';
    case Mention = 'mention';
    case Update = 'update';
    case Message = 'message';
}
