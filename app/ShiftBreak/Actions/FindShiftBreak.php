<?php

declare(strict_types=1);

namespace App\ShiftBreak\Actions;

use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\Properties\StartedAt;
use App\ShiftBreak\ShiftBreak;
use App\ShiftBreak\ShiftBreakCollection;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final class FindShiftBreak
{
    /**
     * The breaks taken during these shifts, earliest first.
     *
     * @param  list<string>  $shiftIds
     */
    public static function forShifts(array $shiftIds): ShiftBreakCollection
    {
        if ($shiftIds === []) {
            return ShiftBreakCollection::make();
        }

        return ShiftBreak::collect(ShiftBreakEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->whereIn(ShiftId::getDatabaseTableColumnName(), $shiftIds)
            ->orderBy(StartedAt::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }
}
