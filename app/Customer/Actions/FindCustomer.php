<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Customer\Customer;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerCollection;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerRelationship;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\BillingType;
use App\Customer\Properties\Email;
use App\Customer\Properties\Id;
use App\Customer\Properties\Name;
use App\Customer\Properties\Prefix;
use App\Customer\Properties\Status;
use App\Customer\Properties\TeamId;
use App\Customer\Properties\TypeId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class FindCustomer
{
    public static function byIdForTeam(?string $id, ?string $teamId): Customer
    {
        if ($id === null || $teamId === null) {
            return Customer::new();
        }

        return Customer::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A page of the team's customers, by name, with their contact and service
     * counts.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public static function paginateForTeam(
        string $teamId,
        ?string $search = null,
        ?string $typeId = null,
        ?CustomerBillingTypeEnum $billingType = null,
        ?CustomerStatusEnum $status = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        return self::query($teamId)
            ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                ->where(Name::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Prefix::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Email::getDatabaseTableColumnName(), 'like', "%{$search}%")))
            ->when($typeId, fn ($query, string $typeId) => $query->where(TypeId::getDatabaseTableColumnName(), $typeId))
            ->when($billingType, fn ($query, CustomerBillingTypeEnum $billingType) => $query->where(BillingType::getDatabaseTableColumnName(), $billingType->value))
            ->when($status, fn ($query, CustomerStatusEnum $status) => $query->where(Status::getDatabaseTableColumnName(), $status->value))
            ->withCount([CustomerRelationship::Contacts->value, CustomerRelationship::Services->value])
            ->orderBy(Name::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CustomerEloquentModel $model): Customer => Customer::fromEloquentModel($model));
    }

    /**
     * Some of the team's customers by id, by name.
     *
     * @param  array<int, string>  $ids
     */
    public static function byIdsForTeam(string $teamId, array $ids): CustomerCollection
    {
        return Customer::collect(self::query($teamId)
            ->whereIn(Id::getDatabaseTableColumnName(), $ids)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->all());
    }

    /**
     * Every customer in the team as id/name pairs, for pickers. A customer
     * with a prefix is named "PREFIX · Name" so it can be found by either.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return self::query($teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get([Id::getDatabaseTableColumnName(), Name::getDatabaseTableColumnName(), Prefix::getDatabaseTableColumnName()])
            ->map(fn (CustomerEloquentModel $customer): array => [
                'id' => $customer->id,
                'name' => $customer->prefix === null ? $customer->name : "{$customer->prefix} · {$customer->name}",
            ]);
    }

    /**
     * @return Builder<CustomerEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return CustomerEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
