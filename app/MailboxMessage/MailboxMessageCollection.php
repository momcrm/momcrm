<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, MailboxMessage>
 */
final class MailboxMessageCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return MailboxMessage::class;
    }
}
