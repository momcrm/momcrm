<?php

declare(strict_types=1);

namespace App\Record\Actions;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldTargetEnum;
use App\Record\Properties\Name;
use App\Record\Properties\Number;
use App\Record\Properties\ObjectId;
use App\Record\Properties\TeamId;
use App\Record\Properties\UpdatedAt;
use App\Record\Record;
use App\Record\RecordEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindRecord
{
    /**
     * What a list of records can be sorted by, mapped to database columns.
     *
     * @var array<string, string>
     */
    public const array SORTS = [
        'name' => 'name',
        'number' => 'number',
        'updated_at' => 'updated_at',
    ];

    /**
     * A page of one object's records, optionally only those whose name
     * contains the search and that match filters on the object's fields. The
     * sort is one of SORTS, or a custom field (`cf:<field id>`).
     *
     * @param  'asc'|'desc'  $direction
     * @param  array<string, string>  $customFields  Filters on custom fields, by field id.
     * @return LengthAwarePaginator<int, Record>
     */
    public static function paginateForObject(
        string $teamId,
        string $objectId,
        ?string $search = null,
        string $sort = 'name',
        string $direction = 'asc',
        int $perPage = 25,
        array $customFields = [],
    ): LengthAwarePaginator {
        $fields = FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Record, objectId: $objectId);
        $sortField = CustomFieldQuery::sortField($sort, $fields);

        return self::query($teamId)
            ->where(ObjectId::getDatabaseTableColumnName(), $objectId)
            ->when($search, fn (Builder $query, string $search) => $query->where(Name::getDatabaseTableColumnName(), 'like', "%{$search}%"))
            ->tap(function (Builder $query) use ($fields, $customFields, $sortField, $sort, $direction): void {
                foreach ($fields as $field) {
                    $value = $customFields[$field->getId()->toString()] ?? null;

                    if ($value !== null) {
                        CustomFieldQuery::filter($query, $field, $value);
                    }
                }

                $sortField->isNotNull()
                    ? CustomFieldQuery::sort($query, $sortField, $direction)
                    : $query->orderBy(self::SORTS[$sort] ?? Name::getDatabaseTableColumnName(), $direction === 'desc' ? 'desc' : 'asc');
            })
            ->orderBy(Number::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (RecordEloquentModel $model): Record => Record::fromEloquentModel($model));
    }

    /**
     * One of a team's records by id; empty if it isn't the team's.
     */
    public static function byIdForTeam(?string $id, string $teamId): Record
    {
        if ($id === null) {
            return Record::new();
        }

        $model = self::query($teamId)->find($id);

        return $model === null ? Record::new() : Record::fromEloquentModel($model);
    }

    /**
     * The team's records in these objects whose name contains the search,
     * most recently changed first, for the workspace search.
     *
     * @param  list<string>  $objectIds
     * @return list<Record>
     */
    public static function searchForTeam(string $teamId, string $search, array $objectIds, int $limit = 5): array
    {
        return array_values(self::query($teamId)
            ->whereIn(ObjectId::getDatabaseTableColumnName(), $objectIds)
            ->where(Name::getDatabaseTableColumnName(), 'like', "%{$search}%")
            ->orderByDesc(UpdatedAt::getDatabaseTableColumnName())
            ->limit($limit)
            ->get()
            ->map(fn (RecordEloquentModel $model): Record => Record::fromEloquentModel($model))
            ->all());
    }

    /**
     * @return Builder<RecordEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return RecordEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
