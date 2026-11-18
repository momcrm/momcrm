<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractString;

/**
 * The address of the person whose email opened the ticket, in lower case.
 * It is kept even when they are not one of the team's contacts, so they can
 * still be answered.
 */
final class RequesterEmail extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'requester_email';
    }
}
