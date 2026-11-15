<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceCollection;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\ArchivedAt;
use App\Choice\Properties\Category;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Name;
use App\Choice\Properties\Position;
use App\Choice\Properties\TeamId;
use Illuminate\Database\Eloquent\Builder;

final class FindChoice
{
    /**
     * A team's choices in one list, in order. Archived choices are left out
     * unless asked for, since they are only kept for records that still use them.
     */
    public static function listForTeam(string $teamId, ChoiceListEnum $list, bool $withArchived = false): ChoiceCollection
    {
        return Choice::collect(
            self::inList($teamId, $list)
                ->when(! $withArchived, fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()))
                ->orderBy(Position::getDatabaseTableColumnName())
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * The choice with this name in one of a team's lists, archived or not;
     * empty if there is none.
     */
    public static function byNameForTeam(string $teamId, ChoiceListEnum $list, string $name): Choice
    {
        $model = self::inList($teamId, $list)->where(Name::getDatabaseTableColumnName(), $name)->first();

        return $model === null ? Choice::new() : Choice::fromEloquentModel($model);
    }

    /**
     * The choice a new record starts with, or the default of one category, such
     * as the status a ticket moves to when it is closed. Empty if there is none.
     */
    public static function defaultForTeam(string $teamId, ChoiceListEnum $list, ?string $category = null): Choice
    {
        $model = self::inList($teamId, $list)
            ->where(IsDefault::getDatabaseTableColumnName(), true)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->when($category !== null, fn (Builder $query) => $query->where(Category::getDatabaseTableColumnName(), $category))
            ->orderBy(Position::getDatabaseTableColumnName())
            ->first();

        return $model === null ? Choice::new() : Choice::fromEloquentModel($model);
    }

    /**
     * @return Builder<ChoiceEloquentModel>
     */
    private static function inList(string $teamId, ChoiceListEnum $list): Builder
    {
        return ChoiceEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(ListName::getDatabaseTableColumnName(), $list->value);
    }
}
