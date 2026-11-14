<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Traits\Enum;

enum TicketRelationship: string
{
    use Enum;

    case Assignee = 'assignee';
    case Contact = 'contact';
    case Customer = 'customer';
}
