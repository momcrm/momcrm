<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Mom\Data\AbstractString;

/**
 * The name of the person whose email opened the ticket.
 */
final class RequesterName extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'requester_name';
    }
}
