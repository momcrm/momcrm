<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\Shared\Traits\Enum;

enum MailboxMessageDirectionEnum: string
{
    use Enum;

    /**
     * Mail that arrived in the mailbox.
     */
    case Inbound = 'inbound';

    /**
     * Mail the team sent from the mailbox.
     */
    case Outbound = 'outbound';
}
