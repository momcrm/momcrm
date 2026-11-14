<?php

declare(strict_types=1);

namespace App\Location\Factories;

use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\Location\Properties\Country;
use App\Location\Properties\CustomerId;
use App\Location\Properties\IsPrimary;
use App\Location\Properties\Name;
use App\Location\Properties\Postcode;
use App\Location\Properties\State;
use App\Location\Properties\StreetOne;
use App\Location\Properties\StreetTwo;
use App\Location\Properties\Suburb;
use App\Location\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationEloquentModel>
 */
final class LocationFactory extends Factory
{
    protected $model = LocationEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->randomElement(['Head office', 'Warehouse', 'Branch']),
            StreetOne::getDatabaseTableColumnName() => fake()->streetAddress(),
            StreetTwo::getDatabaseTableColumnName() => null,
            Suburb::getDatabaseTableColumnName() => fake()->city(),
            State::getDatabaseTableColumnName() => 'WA',
            Postcode::getDatabaseTableColumnName() => fake()->postcode(),
            Country::getDatabaseTableColumnName() => 'Australia',
            IsPrimary::getDatabaseTableColumnName() => false,
        ];
    }
}
