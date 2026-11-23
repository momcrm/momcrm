<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\User\Properties\Id;
use App\User\Properties\Timezone;
use App\User\UserEloquentModel;

final class FillTimezone
{
    /**
     * Saves where someone works from when they have not said, such as the
     * browser's timezone the first time they clock in. A timezone they chose
     * themselves is never replaced.
     */
    public static function whenMissing(string $userId, ?string $timezone): void
    {
        if ($timezone === null || $timezone === '') {
            return;
        }

        UserEloquentModel::query()
            ->where(Id::getDatabaseTableColumnName(), $userId)
            ->where(fn ($query) => $query->whereNull(Timezone::getDatabaseTableColumnName())->orWhere(Timezone::getDatabaseTableColumnName(), ''))
            ->update([Timezone::getDatabaseTableColumnName() => $timezone]);
    }
}
