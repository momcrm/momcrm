<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Service\Properties\CustomerId;
use App\Service\Properties\Id;
use App\Service\Properties\Name;
use App\Service\Properties\TeamId;
use App\Service\Service;
use App\Service\ServiceCollection;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindService
{
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
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
