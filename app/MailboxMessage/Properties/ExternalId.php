<?php

declare(strict_types=1);

namespace App\MailboxMessage\Properties;

use Mom\Data\AbstractString;

/**
 * The outside mail service's own id for the message, which is how it is
 * fetched again or replied to there.
 */
final class ExternalId extends AbstractString
{
    public static function getName(): string
    {
        return 'external_id';
    }
}
