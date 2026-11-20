<?php

declare(strict_types=1);

namespace App\Charge\Factories;

use App\Charge\ChargeEloquentModel;
use App\Charge\Properties\ChargedOn;
use App\Charge\Properties\Currency;
use App\Charge\Properties\CustomerId;
use App\Charge\Properties\Description;
use App\Charge\Properties\Quantity;
use App\Charge\Properties\TeamId;
use App\Charge\Properties\UnitPriceInCents;
use App\Customer\CustomerEloquentModel;
use App\Shared\CurrencyEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChargeEloquentModel>
 */
final class ChargeFactory extends Factory
{
    protected $model = ChargeEloquentModel::class;

    /**
     * An unbilled charge in Australian dollars. Use forCustomer() to keep its
     * team and customer together.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            Description::getDatabaseTableColumnName() => 'Call-out '.fake()->numerify('###'),
            Quantity::getDatabaseTableColumnName() => 1,
            UnitPriceInCents::getDatabaseTableColumnName() => fake()->numberBetween(1000, 50000),
            Currency::getDatabaseTableColumnName() => CurrencyEnum::default()->value,
            ChargedOn::getDatabaseTableColumnName() => now()->toDateString(),
        ];
    }

    /**
     * The customer's team and currency; a customer just made by a factory
     * has no currency loaded, so it is the default one.
     */
    public function forCustomer(CustomerEloquentModel $customer): self
    {
        return $this->state([
            TeamId::getDatabaseTableColumnName() => $customer->team_id,
            CustomerId::getDatabaseTableColumnName() => $customer->id,
            Currency::getDatabaseTableColumnName() => $customer->getAttribute(Currency::getDatabaseTableColumnName()) ?? CurrencyEnum::default()->value,
        ]);
    }
}
