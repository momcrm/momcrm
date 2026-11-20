<?php

declare(strict_types=1);

namespace App\Charge;

use App\Charge\Properties\ChargedOn;
use App\Charge\Properties\Description;
use App\Charge\Properties\ProductId;
use App\Charge\Properties\Quantity;
use App\Charge\Properties\UnitPriceInCents;
use App\Product\Actions\FindProduct;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the charge form, shared by create and update. A charge can
 * start from a product: left blank, its description and price come from the
 * product, priced in the customer's currency.
 */
final class ChargeValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId): array
    {
        return [
            ProductId::getNameForValidation() => ['nullable', 'uuid', Rule::exists(Table::Products->value, 'id')->where('team_id', $teamId)],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Quantity::getNameForValidation() => Quantity::getValidationRulesForCreate($request),
            UnitPriceInCents::getNameForValidation() => UnitPriceInCents::getValidationRulesForCreate($request),
            ChargedOn::getNameForValidation() => ChargedOn::getValidationRulesForCreate($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            Description::getNameForValidation().'.required' => __('Say what the charge is for.'),
            UnitPriceInCents::getNameForValidation().'.required' => __('Give the charge a price.'),
            UnitPriceInCents::getNameForValidation().'.integer' => __('Give the charge a price.'),
            UnitPriceInCents::getNameForValidation().'.min' => __('A price can\'t be negative.'),
            ProductId::getNameForValidation().'.exists' => __('Choose one of your products.'),
        ];
    }

    /**
     * The price typed in dollars, in cents; and what a product gives for
     * what was left blank.
     */
    public static function prepare(FormRequest $request, string $teamId, CurrencyEnum $currency): void
    {
        $price = $request->input('unit_price');
        $product = FindProduct::byIdForTeam(is_string($request->input(ProductId::getNameForValidation())) ? $request->string(ProductId::getNameForValidation())->toString() : null, $teamId);

        $request->merge([
            UnitPriceInCents::getNameForValidation() => match (true) {
                is_numeric($price) => (int) round(((float) $price) * 100),
                ($price === null || $price === '') && $product->isNotNull() => $product->priceIn($currency)?->priceInCents,
                default => $price,
            },
            Description::getNameForValidation() => $request->filled(Description::getNameForValidation()) || $product->isNull()
                ? $request->input(Description::getNameForValidation())
                : $product->getName()->toString(),
            Quantity::getNameForValidation() => $request->input(Quantity::getNameForValidation(), 1),
            ChargedOn::getNameForValidation() => $request->input(ChargedOn::getNameForValidation()) ?: now()->toDateString(),
        ]);
    }
}
