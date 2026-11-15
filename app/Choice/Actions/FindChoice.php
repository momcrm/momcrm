<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceCollection;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\ArchivedAt;
use App\Choice\Properties\Category;
use App\Choice\Properties\Id as ChoiceId;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Name;
use App\Choice\Properties\Position;
use App\Choice\Properties\TeamId;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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
     * One of a team's choices by id, archived or not; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): Choice
    {
        if ($id === null) {
            return Choice::new();
        }

        $model = ChoiceEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);

        return $model === null ? Choice::new() : Choice::fromEloquentModel($model);
    }

    /**
     * How many records use each of a team's choices in a list, by choice id.
     *
     * @return array<string, int>
     */
    public static function usageCountsForTeam(string $teamId, ChoiceListEnum $list): array
    {
        $counts = [];

        foreach ($list->usages() as $usage) {
            DB::table($usage['table']->value)
                ->when(
                    $usage['isPivot'],
                    fn (QueryBuilder $query) => $query->whereIn($usage['column'], self::inList($teamId, $list)->select(ChoiceId::getDatabaseTableColumnName())),
                    fn (QueryBuilder $query) => $query->where('team_id', $teamId),
                )
                ->whereNotNull($usage['column'])
                ->select("{$usage['column']} as choice_id")
                ->selectRaw('count(*) as aggregate')
                ->groupBy($usage['column'])
                ->get()
                ->each(function (object $row) use (&$counts): void {
                    $id = Types::string($row->choice_id);
                    $counts[$id] = ($counts[$id] ?? 0) + Types::int($row->aggregate);
                });
        }

        return $counts;
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
