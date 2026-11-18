<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\Shared\Traits\Enum;

enum MailboxMessageStatusEnum: string
{
    use Enum;

    /**
     * Arrived and became a ticket or a reply on one.
     */
    case Processed = 'processed';

    /**
     * Arrived and was kept without opening a ticket.
     */
    case Ignored = 'ignored';

    /**
     * Waiting to be sent.
     */
    case Pending = 'pending';

    case Sent = 'sent';

    /**
     * Could not be sent; the error says why.
     */
    case Failed = 'failed';
}
