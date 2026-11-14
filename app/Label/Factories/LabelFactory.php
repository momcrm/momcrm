<?php

declare(strict_types=1);

namespace App\Label\Factories;

use App\Label\LabelEloquentModel;
use App\Label\Properties\Color;
use App\Label\Properties\Name;
use App\Label\Properties\TeamId;
use App\Shared\ColorEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabelEloquentModel>
 */
final class LabelFactory extends Factory
{
    protected $model = LabelEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->unique()->word(),
            Color::getDatabaseTableColumnName() => fake()->randomElement(ColorEnum::values()),
        ];
    }
}
