<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Shift\Shift;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\ShiftBreakEloquentModel;
use Illuminate\Support\Facades\DB;

final class DeleteShift
{
    /**
     * Removes a shift and its breaks, one model at a time so the audit log
     * keeps what was there.
     */
    public static function fromShift(Shift $shift): void
    {
        DB::transaction(function () use ($shift): void {
            ShiftBreakEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(ShiftId::getDatabaseTableColumnName(), $shift->getId()->toString())
                ->get()
                ->each(fn (ShiftBreakEloquentModel $break): ?bool => $break->delete());

            ShiftEloquentModel::query()->withoutGlobalScopes()->find($shift->getId()->toString())?->delete();
        });
    }
}
