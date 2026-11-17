<?php

declare(strict_types=1);

namespace App\Record\Factories;

use App\CustomObject\CustomObjectEloquentModel;
use App\Record\Properties\Name;
use App\Record\Properties\Number;
use App\Record\Properties\ObjectId;
use App\Record\Properties\TeamId;
use App\Record\RecordEloquentModel;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecordEloquentModel>
 */
final class RecordFactory extends Factory
{
    protected $model = RecordEloquentModel::class;

    /**
     * A record of a new object in a new team, unless told its object. The
     * team is always the object's.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ObjectId::getDatabaseTableColumnName() => CustomObjectEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => CustomObjectEloquentModel::query()
                ->withoutGlobalScopes()
                ->findOrFail(Types::string($attributes[ObjectId::getDatabaseTableColumnName()]))
                ->team_id,
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            Name::getDatabaseTableColumnName() => fake()->words(2, true),
        ];
    }

    public function of(CustomObjectEloquentModel $object): self
    {
        return $this->state([ObjectId::getDatabaseTableColumnName() => $object->id]);
    }
}
