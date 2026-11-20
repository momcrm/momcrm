<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use App\Rate\RatePrice;
use App\Shared\CurrencyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractCollection;

/**
 * What one unit costs for one month in each currency the rate is sold in, as
 * a list of {currency, price_in_cents, cost_in_cents}. A customer can only be
 * put on a rate with a price in the currency they are billed in.
 */
final class Prices extends AbstractCollection
{
    public const int MAX = 9;

    public static function getName(): string
    {
        return 'prices';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'array', 'min:1', 'max:'.self::MAX];
    }

    /**
     * @return list<RatePrice>
     */
    public function toPrices(): array
    {
        $prices = [];

        foreach ($this->toCollection() as $stored) {
            $price = RatePrice::fromStored($stored);

            if ($price !== null) {
                $prices[] = $price;
            }
        }

        return $prices;
    }

    /**
     * The price in one currency, or null when the rate isn't sold in it.
     */
    public function in(CurrencyEnum $currency): ?RatePrice
    {
        foreach ($this->toPrices() as $price) {
            if ($price->currency === $currency) {
                return $price;
            }
        }

        return null;
    }
}
