<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What a team's ticket status means to the app, whatever the team calls it:
 * open tickets are still being worked on, closed ones are finished.
 */
enum TicketStatusCategoryEnum: string
{
    use Enum;
    use EnumOptions;

    case Closed = 'closed';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Closed',
            self::Open => 'Open',
        };
    }
}
