<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerRelationship;
use App\Customer\Properties\Email;
use App\Customer\Properties\Id;
use App\Customer\Properties\Name;
use App\Customer\Properties\TeamId;
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
     * @return LengthAwarePaginator<int, CustomerEloquentModel>
     */
    public static function paginateForTeam(string $teamId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return self::query($teamId)
            ->when($search, fn ($query, string $search) => $query->where(fn ($query) => $query
                ->where(Name::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Email::getDatabaseTableColumnName(), 'like', "%{$search}%")))
            ->withCount(CustomerRelationship::Contacts->value)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Every customer in the team as id/name pairs, for pickers.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return self::query($teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get([Id::getDatabaseTableColumnName(), Name::getDatabaseTableColumnName()])
            ->map(fn (CustomerEloquentModel $customer): array => ['id' => $customer->id, 'name' => $customer->name]);
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
