<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\ShiftBreak\Properties\EndedAt;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\ShiftBreakEloquentModel;
use Carbon\CarbonImmutable;

final class EndBreak
{
    /**
     * Ends the member's break and carries on with the shift. Returns whether
     * they were on one.
     */
    public static function forMember(string $teamId, string $userId, ?CarbonImmutable $at = null): bool
    {
        $shift = FindShift::openForMember($teamId, $userId);

        return $shift->isNotNull() && self::forShift($shift->getId()->toString(), $at ?? CarbonImmutable::now());
    }

    /**
     * Ends the shift's running break, never before it started. Returns
     * whether there was one.
     */
    public static function forShift(string $shiftId, CarbonImmutable $at): bool
    {
        $model = ShiftBreakEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(ShiftId::getDatabaseTableColumnName(), $shiftId)
            ->whereNull(EndedAt::getDatabaseTableColumnName())
            ->first();

        if (! $model instanceof ShiftBreakEloquentModel) {
            return false;
        }

        $model->update([EndedAt::getDatabaseTableColumnName() => $at->utc()->startOfMinute()->max($model->started_at)->toDateTimeString()]);

        return true;
    }
}
