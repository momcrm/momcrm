<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The mailbox the message arrived in or was sent from; empty once that
 * mailbox is disconnected.
 */
final class MailboxId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'mailbox_id';
    }
}
