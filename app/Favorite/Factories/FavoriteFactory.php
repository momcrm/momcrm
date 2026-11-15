<?php

declare(strict_types=1);

namespace App\Favorite\Factories;

use App\Favorite\FavoriteEloquentModel;
use App\Favorite\Properties\FavoritableId;
use App\Favorite\Properties\FavoritableType;
use App\Favorite\Properties\TeamId;
use App\Favorite\Properties\UserId;
use App\Project\ProjectEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<FavoriteEloquentModel>
 */
final class FavoriteFactory extends Factory
{
    protected $model = FavoriteEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            FavoritableType::getDatabaseTableColumnName() => MorphMapKeyEnum::Project->value,
            FavoritableId::getDatabaseTableColumnName() => ProjectEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(ProjectEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[FavoritableId::getDatabaseTableColumnName()])
                ->value('team_id')),
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
        ];
    }

    /**
     * A favourite on the given record, in that record's team.
     */
    public function forFavoritable(Model $favoritable, ?UserEloquentModel $user = null): self
    {
        return $this->state([
            TeamId::getDatabaseTableColumnName() => $favoritable->getAttribute('team_id'),
            FavoritableType::getDatabaseTableColumnName() => $favoritable->getMorphClass(),
            FavoritableId::getDatabaseTableColumnName() => $favoritable->getKey(),
            ...($user ? [UserId::getDatabaseTableColumnName() => $user->id] : []),
        ]);
    }
}
