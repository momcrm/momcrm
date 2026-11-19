<?php

declare(strict_types=1);

namespace App\Rate;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Rate>
 */
final class RateCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Rate::class;
    }

    /**
     * Each rate as the frontend shows it, in order.
     *
     * @return list<array{id: string, name: string, description: ?string, price_in_cents: int, cost_in_cents: ?int, billing_period_in_months: int, prorates_first_month: bool, is_archived: bool}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (Rate $rate): array => $rate->toSummary())->all());
    }
}
