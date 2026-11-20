<?php

declare(strict_types=1);

namespace App\Product;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Product>
 */
final class ProductCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Product::class;
    }

    /**
     * @return list<array{id: string, name: string, description: ?string, prices: list<array{currency: string, price_in_cents: int, cost_in_cents: ?int}>, is_archived: bool}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (Product $product): array => $product->toSummary())->all());
    }
}
