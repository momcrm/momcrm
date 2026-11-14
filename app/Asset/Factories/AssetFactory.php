<?php

declare(strict_types=1);

namespace App\Asset\Factories;

use App\Asset\AssetEloquentModel;
use App\Asset\AssetStatusEnum;
use App\Asset\Properties\CustomerId;
use App\Asset\Properties\Description;
use App\Asset\Properties\LocationId;
use App\Asset\Properties\Name;
use App\Asset\Properties\SerialNumber;
use App\Asset\Properties\Status;
use App\Asset\Properties\TeamId;
use App\Customer\CustomerEloquentModel;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetEloquentModel>
 */
final class AssetFactory extends Factory
{
    protected $model = AssetEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            LocationId::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => fake()->randomElement(['Reception PC', 'Firewall', 'Printer', 'NAS']),
            SerialNumber::getDatabaseTableColumnName() => fake()->bothify('SN-####-????'),
            Description::getDatabaseTableColumnName() => null,
            Status::getDatabaseTableColumnName() => AssetStatusEnum::Active->value,
        ];
    }
}
