<?php

declare(strict_types=1);

namespace App\LeaveRequest;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, LeaveRequest>
 */
final class LeaveRequestCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return LeaveRequest::class;
    }

    /**
     * The member's leave on this date (YYYY-MM-DD), or an empty request.
     * A full day wins over a half day if both somehow exist.
     */
    public function forMemberOn(string $userId, string $date): LeaveRequest
    {
        return $this
            ->filter(fn (LeaveRequest $leave): bool => $leave->getUserId()->toNullableString() === $userId && $leave->covers($date))
            ->sortBy(fn (LeaveRequest $leave): int => $leave->getPart()->toEnum()->isHalfDay() ? 1 : 0)
            ->first();
    }
}
