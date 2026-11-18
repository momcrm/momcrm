<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractCollection;

/**
 * The ids of the earlier messages in the thread, oldest first, from the
 * References header.
 */
final class ReferenceIds extends AbstractCollection
{
    public static function getName(): string
    {
        return 'reference_ids';
    }
}
