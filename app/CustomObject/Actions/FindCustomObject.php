<?php

declare(strict_types=1);

namespace App\CustomObject\Actions;

use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectCollection;
use App\CustomObject\CustomObjectEloquentModel;
use App\CustomObject\Properties\NamePlural;
use App\CustomObject\Properties\Position;
use App\CustomObject\Properties\TeamId;
use App\Shared\RequestMemo;

final class FindCustomObject
{
    /**
     * A team's objects, in order. Archived objects are left out unless asked
     * for. Read once per request.
     */
    public static function listForTeam(string $teamId, bool $withArchived = false): CustomObjectCollection
    {
        $objects = RequestMemo::remember("custom-objects.{$teamId}", fn (): CustomObjectCollection => CustomObject::collect(
            CustomObjectEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(Position::getDatabaseTableColumnName())
                ->orderBy(NamePlural::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        ));

        return $withArchived ? $objects : $objects->filter(fn (CustomObject $object): bool => ! $object->isArchived())->values();
    }

    /**
     * Drops what was read for a team earlier in this request, after its
     * objects change.
     */
    public static function forget(string $teamId): void
    {
        RequestMemo::forget("custom-objects.{$teamId}");
    }

    /**
     * One of a team's objects by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): CustomObject
    {
        if ($id === null) {
            return CustomObject::new();
        }

        $model = CustomObjectEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? CustomObject::new() : CustomObject::fromEloquentModel($model);
    }
}
