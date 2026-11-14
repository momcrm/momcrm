<?php

declare(strict_types=1);

namespace App\Favorite\Actions;

use App\Favorite\FavoritableTypeEnum;
use App\Favorite\Favorite;
use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\Id;
use App\Favorite\Properties\UserId;

/**
 * Stars a record for someone, or unstars it if it already is. Returns whether
 * it is starred afterwards.
 */
final readonly class ToggleFavorite
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private FavoritableTypeEnum $type,
        private string $favoritableId,
    ) {}

    public function __invoke(): bool
    {
        $deleted = FavoriteEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(UserId::getDatabaseTableColumnName(), $this->userId)
            ->where(FavoritableType::getDatabaseTableColumnName(), $this->type->value)
            ->where(FavoritableId::getDatabaseTableColumnName(), $this->favoritableId)
            ->delete();

        if ($deleted > 0) {
            return false;
        }

        FavoriteEloquentModel::query()->create(Favorite::new()
            ->setId(Id::generate())
            ->setTeamId($this->teamId)
            ->setUserId($this->userId)
            ->setFavoritableType($this->type->morphMapKey())
            ->setFavoritableId($this->favoritableId)
            ->forDatabaseCreate());

        return true;
    }

    public static function for(string $teamId, string $userId, FavoritableTypeEnum $type, string $favoritableId): bool
    {
        return new self($teamId, $userId, $type, $favoritableId)->handle();
    }

    public function handle(): bool
    {
        return $this->__invoke();
    }

    public function execute(): bool
    {
        return $this->__invoke();
    }
}
