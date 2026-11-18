<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Mailbox>
 */
final class MailboxCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Mailbox::class;
    }
}
