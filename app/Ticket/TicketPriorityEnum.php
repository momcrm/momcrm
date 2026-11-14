<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum TicketPriorityEnum: string
{
    use Enum;
    use EnumOptions;

    case High = 'high';
    case Low = 'low';
    case Normal = 'normal';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::High => 'High',
            self::Low => 'Low',
            self::Normal => 'Normal',
            self::Urgent => 'Urgent',
        };
    }
}
