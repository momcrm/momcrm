<?php

declare(strict_types=1);

namespace App\Holiday\Factories;

use App\Holiday\HolidayEloquentModel;
use App\Holiday\Properties\Date;
use App\Holiday\Properties\Name;
use App\Holiday\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolidayEloquentModel>
 */
final class HolidayFactory extends Factory
{
    protected $model = HolidayEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Date::getDatabaseTableColumnName() => fake()->unique()->date(),
            Name::getDatabaseTableColumnName() => 'Public holiday',
        ];
    }

    public function forTeam(string $teamId): self
    {
        return $this->state([TeamId::getDatabaseTableColumnName() => $teamId]);
    }

    public function on(string $date): self
    {
        return $this->state([Date::getDatabaseTableColumnName() => $date]);
    }
}
