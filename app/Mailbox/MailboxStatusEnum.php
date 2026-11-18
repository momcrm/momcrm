<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\Shared\Traits\Enum;

enum MailboxStatusEnum: string
{
    use Enum;

    case Connected = 'connected';

    /**
     * Microsoft no longer accepts the stored sign-in, so someone has to
     * connect the mailbox again before it is read or sent from.
     */
    case NeedsReconnect = 'needs_reconnect';
}
