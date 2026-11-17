<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomField;
use App\CustomField\CustomFieldCollection;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\ObjectId;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\TeamId;
use App\Shared\RequestMemo;

final class FindCustomField
{
    /**
     * A team's fields on one kind of record, in order: a built-in kind, or
     * the records of one custom object. Archived fields are left out unless
     * asked for. Read once per request.
     */
    public static function listForTeam(string $teamId, CustomFieldTargetEnum $target, bool $withArchived = false, ?string $objectId = null): CustomFieldCollection
    {
        $fields = RequestMemo::remember("custom-fields.{$teamId}.{$target->value}.{$objectId}", fn (): CustomFieldCollection => CustomField::collect(
            CustomFieldEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->where(Target::getDatabaseTableColumnName(), $target->value)
                ->where(ObjectId::getDatabaseTableColumnName(), $objectId)
                ->orderBy(Position::getDatabaseTableColumnName())
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        ));

        return $withArchived ? $fields : $fields->filter(fn (CustomField $field): bool => ! $field->isArchived())->values();
    }

    /**
     * Drops what was read for a team earlier in this request, after its
     * fields change.
     */
    public static function forget(string $teamId): void
    {
        RequestMemo::forget("custom-fields.{$teamId}.");
    }

    /**
     * One of a team's fields by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): CustomField
    {
        if ($id === null) {
            return CustomField::new();
        }

        $model = CustomFieldEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? CustomField::new() : CustomField::fromEloquentModel($model);
    }
}
