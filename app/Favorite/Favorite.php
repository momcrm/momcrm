<?php

declare(strict_types=1);

namespace App\Favorite;

use App\Favorite\Factories\FavoriteFactory;
use App\Favorite\Properties\CreatedAt;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\Id;
use App\Favorite\Properties\TeamId;
use App\Favorite\Properties\UserId;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<FavoriteEloquentModel>
 */
final class Favorite extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public FavoritableId $favoritableId,
        public FavoritableType $favoritableType,
        public Id $id,
        public TeamId $teamId,
        public UserId $userId,
    ) {}

    public static function getFactory(): FavoriteFactory
    {
        return FavoriteEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): FavoriteCollection
    {
        if ($items instanceof FavoriteCollection) {
            return $items;
        }

        return FavoriteCollection::make(parent::collect($items, $method)->all());
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getFavoritableId(): FavoritableId
    {
        return $this->favoritableId;
    }

    public function setFavoritableId(mixed $favoritableId): self
    {
        $this->favoritableId = new FavoritableId($favoritableId instanceof AbstractValue ? $favoritableId->toValue() : $favoritableId);

        return $this;
    }

    public function getFavoritableType(): FavoritableType
    {
        return $this->favoritableType;
    }

    public function setFavoritableType(mixed $favoritableType): self
    {
        $this->favoritableType = new FavoritableType($favoritableType instanceof AbstractValue ? $favoritableType->toValue() : $favoritableType);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function setUserId(mixed $userId): self
    {
        $this->userId = new UserId($userId instanceof AbstractValue ? $userId->toValue() : $userId);

        return $this;
    }
}
