<?php

declare(strict_types=1);

namespace App\Rate;

use App\Shared\CurrencyEnum;
use App\Shared\Types;

/**
 * What one unit of a rate costs for one month, before tax, in one currency,
 * and what it costs the team when known.
 */
final readonly class RatePrice
{
    public function __construct(
        public CurrencyEnum $currency,
        public int $priceInCents,
        public ?int $costInCents = null,
    ) {}

    /**
     * One entry of a rate's stored prices; null when it names no currency we
     * know.
     */
    public static function fromStored(mixed $price): ?self
    {
        if (! is_array($price)) {
            return null;
        }

        $currency = is_string($price['currency'] ?? null) ? CurrencyEnum::tryFrom($price['currency']) : null;

        if ($currency === null) {
            return null;
        }

        $cost = $price['cost_in_cents'] ?? null;

        return new self($currency, Types::intOrZero($price['price_in_cents'] ?? null), $cost === null ? null : Types::int($cost));
    }

    /**
     * @return array{currency: string, price_in_cents: int, cost_in_cents: ?int}
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency->value,
            'price_in_cents' => $this->priceInCents,
            'cost_in_cents' => $this->costInCents,
        ];
    }
}
