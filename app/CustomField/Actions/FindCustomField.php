<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomField;
use App\CustomField\CustomFieldCollection;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\Properties\ArchivedAt;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\TeamId;
use Illuminate\Database\Eloquent\Builder;

final class FindCustomField
{
    /**
     * A team's fields on one kind of record, in order. Archived fields are
     * left out unless asked for.
     */
    public static function listForTeam(string $teamId, CustomFieldTargetEnum $target, bool $withArchived = false): CustomFieldCollection
    {
        return CustomField::collect(
            CustomFieldEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->where(Target::getDatabaseTableColumnName(), $target->value)
                ->when(! $withArchived, fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()))
                ->orderBy(Position::getDatabaseTableColumnName())
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
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
