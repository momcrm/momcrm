<?php

declare(strict_types=1);

namespace App\Service\Factories;

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\ContactId;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\RateId;
use App\Service\Properties\ShortName;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
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
     * An active service on a rate of the same team, billed by its rate's period.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            RateId::getDatabaseTableColumnName() => fn (array $attributes): string => RateEloquentModel::factory()->create([TeamId::getDatabaseTableColumnName() => $attributes[TeamId::getDatabaseTableColumnName()]])->id,
            LocationId::getDatabaseTableColumnName() => null,
            ContactId::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => fake()->randomElement(['Managed IT support', 'Microsoft 365', 'Backup']),
            ShortName::getDatabaseTableColumnName() => fake()->unique()->slug(2),
            Note::getDatabaseTableColumnName() => null,
            Quantity::getDatabaseTableColumnName() => 1,
            BillingPeriodInMonths::getDatabaseTableColumnName() => null,
            BillingStartsOn::getDatabaseTableColumnName() => now()->toDateString(),
            EndsOn::getDatabaseTableColumnName() => null,
            Status::getDatabaseTableColumnName() => ServiceStatusEnum::Active->value,
            AutoRenew::getDatabaseTableColumnName() => false,
        ];
    }

    public function inactive(): self
    {
        return $this->state([Status::getDatabaseTableColumnName() => ServiceStatusEnum::Inactive->value]);
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
