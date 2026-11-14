<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

enum TicketStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Closed = 'closed';
    case InProgress = 'in_progress';
    case New = 'new';
    case WaitingOnCustomer = 'waiting_on_customer';
    case WaitingOnSupplier = 'waiting_on_supplier';

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Closed',
            self::InProgress => 'In progress',
            self::New => 'New',
            self::WaitingOnCustomer => 'Waiting on customer',
            self::WaitingOnSupplier => 'Waiting on supplier',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }

    /**
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->isOpen()),
        ));
    }
}
