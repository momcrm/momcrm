<?php

declare(strict_types=1);

namespace App\Product\Factories;

use App\Product\ProductEloquentModel;
use App\Product\Properties\ArchivedAt;
use App\Product\Properties\Name;
use App\Product\Properties\Prices;
use App\Product\Properties\TeamId;
use App\Shared\CurrencyEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductEloquentModel>
 */
final class ProductFactory extends Factory
{
    protected $model = ProductEloquentModel::class;

    /**
     * A product priced in Australian dollars.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => 'Product '.fake()->unique()->numerify('#####'),
            Prices::getDatabaseTableColumnName() => [['currency' => CurrencyEnum::default()->value, 'price_in_cents' => fake()->numberBetween(1000, 200000), 'cost_in_cents' => null]],
        ];
    }

    /**
     * Priced at an amount in each currency given, e.g. ['AUD' => 9500, 'USD' => 6200].
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
