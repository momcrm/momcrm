<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Ticket>
 */
final class TicketCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Ticket::class;
    }
}
