<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Where a timesheet is. A period nobody has submitted has no timesheet yet
 * and reads as open.
 */
enum TimesheetStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Approved = 'approved';
    case Rejected = 'rejected';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Rejected => 'Sent back',
            self::Submitted => 'Submitted',
        };
    }

    /**
     * Whether the member can no longer change their own time in it.
     */
    public function locksMember(): bool
    {
        return $this !== self::Rejected;
    }
}
