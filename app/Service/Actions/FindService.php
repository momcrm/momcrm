<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Customer\Properties\Name as CustomerName;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\CustomerId;
use App\Service\Properties\Id;
use App\Service\Properties\Name;
use App\Service\Properties\RateId;
use App\Service\Properties\ShortName;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Service;
use App\Service\ServiceCollection;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceLifecycleEnum;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindService
{
    /**
     * The columns the services list can sort by, and the column each sorts on.
     *
     * @var array<string, string>
     */
    public const array SORTS = [
        'name' => 'services.name',
        'short_name' => 'services.short_name',
        'customer' => 'customers.name',
        'billing_starts_on' => 'services.billing_starts_on',
        'updated_at' => 'services.updated_at',
    ];

    /**
     * A page of the team's services across customers, for the services list.
     * With no lifecycle given, archived services are left out.
     *
     * @return LengthAwarePaginator<int, Service>
     */
    public static function paginateForTeam(
        string $teamId,
        ?string $search = null,
        ?ServiceLifecycleEnum $lifecycle = null,
        ?string $rateId = null,
        ?string $customerId = null,
        ?bool $autoRenew = null,
        string $sort = 'name',
        string $direction = 'asc',
        int $perPage = 50,
    ): LengthAwarePaginator {
        $services = Table::Services->value;
        $customers = Table::Customers->value;

        return self::query($teamId)
            ->join($customers, "{$customers}.id", '=', CustomerId::getDatabaseTableColumnNameWithTable($services))
            ->select("{$services}.*")
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Name::getDatabaseTableColumnNameWithTable($services), 'like', "%{$search}%")
                ->orWhere(ShortName::getDatabaseTableColumnNameWithTable($services), 'like', "%{$search}%")
                ->orWhere(CustomerName::getDatabaseTableColumnNameWithTable($customers), 'like', "%{$search}%")))
            ->tap(fn (Builder $query) => match ($lifecycle) {
                null => $query->whereNull(ArchivedAt::getDatabaseTableColumnNameWithTable($services)),
                ServiceLifecycleEnum::Archived => $query->whereNotNull(ArchivedAt::getDatabaseTableColumnNameWithTable($services)),
                default => $query
                    ->whereNull(ArchivedAt::getDatabaseTableColumnNameWithTable($services))
                    ->where(Status::getDatabaseTableColumnNameWithTable($services), $lifecycle->value),
            })
            ->when($rateId, fn (Builder $query, string $rateId) => $query->where(RateId::getDatabaseTableColumnNameWithTable($services), $rateId))
            ->when($customerId, fn (Builder $query, string $customerId) => $query->where(CustomerId::getDatabaseTableColumnNameWithTable($services), $customerId))
            ->when($autoRenew !== null, fn (Builder $query) => $query->where(AutoRenew::getDatabaseTableColumnNameWithTable($services), $autoRenew))
            ->orderBy(self::SORTS[$sort] ?? self::SORTS['name'], $direction === 'desc' ? 'desc' : 'asc')
            ->orderBy(Id::getDatabaseTableColumnNameWithTable($services))
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ServiceEloquentModel $model): Service => Service::fromEloquentModel($model));
    }

    /**
     * Some of the team's services by id.
     *
     * @param  list<string>  $ids
     */
    public static function byIdsForTeam(string $teamId, array $ids): ServiceCollection
    {
        return Service::collect(self::query($teamId)->whereIn(Id::getDatabaseTableColumnName(), $ids)->get()->all(), 'fromEloquentModel');
    }

    public static function byIdForTeam(?string $id, ?string $teamId): Service
    {
        if ($id === null || $teamId === null) {
            return Service::new();
        }

        return Service::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    public static function forCustomer(string $customerId, string $teamId): ServiceCollection
    {
        $models = self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get();

        return Service::collect($models->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<ServiceEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnNameWithTable(Table::Services->value), $teamId);
    }
}
