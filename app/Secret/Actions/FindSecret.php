<?php

declare(strict_types=1);

namespace App\Secret\Actions;

use App\Secret\Properties\AssetId;
use App\Secret\Properties\CustomerId;
use App\Secret\Properties\Description;
use App\Secret\Properties\Id;
use App\Secret\Properties\TeamId;
use App\Secret\Properties\Title;
use App\Secret\Secret;
use App\Secret\SecretCollection;
use App\Secret\SecretEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindSecret
{
    public static function byIdForTeam(?string $id, ?string $teamId): Secret
    {
        if ($id === null || $teamId === null) {
            return Secret::new();
        }

        return Secret::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A customer's secrets by title, optionally only one asset's, or those
     * matching a search of title and description.
     */
    public static function forCustomer(string $customerId, string $teamId, ?string $assetId = null, ?string $search = null): SecretCollection
    {
        return Secret::collect(self::query($teamId)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->when($assetId, fn (Builder $query, string $assetId) => $query->where(AssetId::getDatabaseTableColumnName(), $assetId))
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Description::getDatabaseTableColumnName(), 'like', "%{$search}%")))
            ->orderBy(Title::getDatabaseTableColumnName())
            ->get()
            ->all(), 'fromEloquentModel');
    }

    public static function countForCustomer(string $customerId, string $teamId): int
    {
        return self::query($teamId)->where(CustomerId::getDatabaseTableColumnName(), $customerId)->count();
    }

    /**
     * @return Builder<SecretEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return SecretEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
