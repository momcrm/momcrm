<?php

declare(strict_types=1);

namespace App\Asset\Actions;

use App\Asset\Asset;
use App\Asset\AssetCollection;
use App\Asset\AssetEloquentModel;
use App\Asset\Properties\CustomerId;
use App\Asset\Properties\Id;
use App\Asset\Properties\Name;
use App\Asset\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindAsset
{
    public static function byIdForTeam(?string $id, ?string $teamId): Asset
    {
        if ($id === null || $teamId === null) {
            return Asset::new();
        }

        return Asset::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    public static function forCustomer(string $customerId, string $teamId): AssetCollection
    {
        $models = self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get();

        return Asset::collect($models->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<AssetEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return AssetEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
