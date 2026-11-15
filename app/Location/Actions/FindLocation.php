<?php

declare(strict_types=1);

namespace App\Location\Actions;

use App\Location\Location;
use App\Location\LocationEloquentModel;
use App\Location\Properties\CustomerId;
use App\Location\Properties\Id;
use App\Location\Properties\Name;
use App\Location\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FindLocation
{
    public static function byIdForTeam(?string $id, ?string $teamId): Location
    {
        if ($id === null || $teamId === null) {
            return Location::new();
        }

        return Location::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * @return Collection<int, Location>
     */
    public static function forCustomer(string $customerId, string $teamId): Collection
    {
        $models = self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get();

        return Location::collect($models->all(), 'fromEloquentModel');
    }

    /**
     * A customer's locations as id/name pairs, for pickers.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    public static function optionsForCustomer(string $customerId, string $teamId): Collection
    {
        return self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (LocationEloquentModel $model): array => ['id' => $model->id, 'name' => $model->name]);
    }

    /**
     * @return Builder<LocationEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return LocationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
