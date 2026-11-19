<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Properties\DueOn;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Notes;
use App\Invoice\Properties\PurchaseOrder;
use App\Invoice\Properties\TaxRateBasisPoints;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by creating and editing an invoice. Amounts arrive in dollars
 * and the tax rate as a percentage; normalise() converts them to cents and
 * basis points before validation.
 */
final class InvoiceValidationRules
{
    public const int MAX_ITEMS = 100;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $customerId): array
    {
        return [
            IssuedOn::getNameForValidation() => IssuedOn::getValidationRulesForCreate($request),
            DueOn::getNameForValidation() => DueOn::getValidationRulesForCreate($request),
            TaxRateBasisPoints::getNameForValidation() => TaxRateBasisPoints::getValidationRulesForCreate($request),
            PurchaseOrder::getNameForValidation() => PurchaseOrder::getValidationRulesForCreate($request),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($request),
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price_in_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'items.*.service_id' => ['nullable', 'uuid', Rule::exists(Table::Services->value, 'id')->where('customer_id', $customerId)],
            'items.*.rental_starts_on' => ['nullable', 'date', 'required_with:items.*.rental_ends_on'],
            'items.*.rental_ends_on' => ['nullable', 'date', 'required_with:items.*.rental_starts_on', 'after_or_equal:items.*.rental_starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'items.required' => __('Add at least one line item.'),
            'items.min' => __('Add at least one line item.'),
            'items.*.description.required' => __('Describe each line item.'),
            'items.*.unit_price_in_cents.required' => __('Give each line item a price.'),
            'items.*.unit_price_in_cents.integer' => __('Give each line item a price.'),
        ];
    }

    /**
     * Dollars to cents and percent to basis points, so the rules see stored units.
     */
    public static function normalise(FormRequest $request): void
    {
        $taxRate = $request->input('tax_rate');
        $items = $request->input('items');

        $request->merge([
            TaxRateBasisPoints::getNameForValidation() => is_numeric($taxRate) ? (int) round(((float) $taxRate) * 100) : $taxRate,
            'items' => is_array($items) ? array_map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                $price = $item['unit_price'] ?? null;
                $item['unit_price_in_cents'] = is_numeric($price) ? (int) round(((float) $price) * 100) : $price;
                unset($item['unit_price']);

                return $item;
            }, $items) : $items,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id: ?string, rental_starts_on: ?string, rental_ends_on: ?string}>
     */
    public static function items(array $validated): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'] ?? [];

        return array_values(array_map(fn (array $item): array => [
            'description' => Types::string($item['description']),
            'quantity' => Types::int($item['quantity']),
            'unit_price_in_cents' => Types::int($item['unit_price_in_cents']),
            'service_id' => Types::nullableString($item['service_id'] ?? null),
            'rental_starts_on' => isset($item['service_id']) ? Types::nullableString($item['rental_starts_on'] ?? null) : null,
            'rental_ends_on' => isset($item['service_id']) ? Types::nullableString($item['rental_ends_on'] ?? null) : null,
        ], $items));
    }
}
