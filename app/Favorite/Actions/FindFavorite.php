<?php

declare(strict_types=1);

namespace App\Favorite\Actions;

use App\Favorite\FavoritableTypeEnum;
use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\TeamId;
use App\Favorite\Properties\UserId;
use App\Shared\Types;

final class FindFavorite
{
    /**
     * The ids of the records of one kind someone has starred in a team.
     *
     * @return array<int, string>
     */
    public static function idsForUser(string $teamId, string $userId, FavoritableTypeEnum $type): array
    {
        return FavoriteEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->where(FavoritableType::getDatabaseTableColumnName(), $type->value)
            ->pluck(FavoritableId::getDatabaseTableColumnName())
            ->map(Types::string(...))
            ->values()
            ->all();
    }
}
