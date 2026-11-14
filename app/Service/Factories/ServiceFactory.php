<?php

declare(strict_types=1);

namespace App\Service\Factories;

use App\Customer\CustomerEloquentModel;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\StartsOn;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Properties\UnitPriceInCents;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceStatusEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceEloquentModel>
 */
final class ServiceFactory extends Factory
{
    protected $model = ServiceEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            LocationId::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => fake()->randomElement(['Managed IT support', 'Microsoft 365', 'Backup']),
            Note::getDatabaseTableColumnName() => null,
            Quantity::getDatabaseTableColumnName() => 1,
            UnitPriceInCents::getDatabaseTableColumnName() => fake()->numberBetween(1000, 50000),
            BillingPeriodInMonths::getDatabaseTableColumnName() => 1,
            StartsOn::getDatabaseTableColumnName() => now()->toDateString(),
            EndsOn::getDatabaseTableColumnName() => null,
            Status::getDatabaseTableColumnName() => ServiceStatusEnum::Active->value,
        ];
    }
}
