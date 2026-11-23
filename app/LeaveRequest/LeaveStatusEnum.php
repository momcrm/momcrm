<?php

declare(strict_types=1);

namespace App\LeaveRequest;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Where a leave request is. A code enum, not a team list, because the app
 * acts on it: only approved leave takes days off people's schedules.
 */
enum LeaveStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Declined = 'declined';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Cancelled => 'Cancelled',
            self::Declined => 'Declined',
            self::Pending => 'Waiting for approval',
        };
    }

    /**
     * Statuses that still claim the days, so another request can't overlap them.
     *
     * @return list<string>
     */
    public static function active(): array
    {
        return [self::Pending->value, self::Approved->value];
    }
}
