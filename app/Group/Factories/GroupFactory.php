<?php

declare(strict_types=1);

namespace App\Group\Factories;

use App\Group\GroupEloquentModel;
use App\Group\Properties\Description;
use App\Group\Properties\Hue;
use App\Group\Properties\Icon;
use App\Group\Properties\Name;
use App\Group\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupEloquentModel>
 */
final class GroupFactory extends Factory
{
    protected $model = GroupEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->unique()->jobTitle(),
            Description::getDatabaseTableColumnName() => null,
            Icon::getDatabaseTableColumnName() => null,
            Hue::getDatabaseTableColumnName() => fake()->numberBetween(0, 359),
        ];
    }
}
