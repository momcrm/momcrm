<?php

declare(strict_types=1);

namespace App\Rate\Factories;

use App\Rate\Properties\ArchivedAt;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Properties\Name;
use App\Rate\Properties\Prices;
use App\Rate\Properties\ProratesFirstMonth;
use App\Rate\Properties\TeamId;
use App\Rate\RateEloquentModel;
use App\Shared\CurrencyEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateEloquentModel>
 */
final class RateFactory extends Factory
{
    protected $model = RateEloquentModel::class;

    /**
     * A monthly rate, priced in Australian dollars, that prorates its first
     * month.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->unique()->words(3, true),
            Prices::getDatabaseTableColumnName() => [['currency' => CurrencyEnum::default()->value, 'price_in_cents' => fake()->numberBetween(500, 50000), 'cost_in_cents' => null]],
            BillingPeriodInMonths::getDatabaseTableColumnName() => 1,
            ProratesFirstMonth::getDatabaseTableColumnName() => true,
        ];
    }

    /**
     * Priced at a monthly amount in each currency given, e.g. ['AUD' => 9500, 'USD' => 6200].
     *
     * @param  array<string, int>  $centsByCurrency
     */
    public function priced(array $centsByCurrency): self
    {
        $prices = [];

        foreach ($centsByCurrency as $currency => $cents) {
            $prices[] = ['currency' => $currency, 'price_in_cents' => $cents, 'cost_in_cents' => null];
        }

        return $this->state([Prices::getDatabaseTableColumnName() => $prices]);
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
