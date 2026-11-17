<?php

declare(strict_types=1);

namespace App\CustomObject\Factories;

use App\CustomObject\CustomObjectEloquentModel;
use App\CustomObject\Properties\ArchivedAt;
use App\CustomObject\Properties\Hue;
use App\CustomObject\Properties\Icon;
use App\CustomObject\Properties\Name;
use App\CustomObject\Properties\NamePlural;
use App\CustomObject\Properties\Position;
use App\CustomObject\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomObjectEloquentModel>
 */
final class CustomObjectFactory extends Factory
{
    protected $model = CustomObjectEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst((string) fake()->unique()->word());

        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => $name,
            NamePlural::getDatabaseTableColumnName() => "{$name}s",
            Icon::getDatabaseTableColumnName() => 'box',
            Hue::getDatabaseTableColumnName() => 210,
            Position::getDatabaseTableColumnName() => 0,
        ];
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
