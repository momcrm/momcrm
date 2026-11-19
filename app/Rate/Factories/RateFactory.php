<?php

declare(strict_types=1);

namespace App\Rate\Factories;

use App\Rate\Properties\ArchivedAt;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Properties\Name;
use App\Rate\Properties\PriceInCents;
use App\Rate\Properties\ProratesFirstMonth;
use App\Rate\Properties\TeamId;
use App\Rate\RateEloquentModel;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateEloquentModel>
 */
final class RateFactory extends Factory
{
    protected $model = RateEloquentModel::class;

    /**
     * A monthly rate that prorates its first month.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->unique()->words(3, true),
            PriceInCents::getDatabaseTableColumnName() => fake()->numberBetween(500, 50000),
            BillingPeriodInMonths::getDatabaseTableColumnName() => 1,
            ProratesFirstMonth::getDatabaseTableColumnName() => true,
        ];
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
