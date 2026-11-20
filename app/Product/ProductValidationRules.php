<?php

declare(strict_types=1);

namespace App\Product;

use App\Product\Properties\Description;
use App\Product\Properties\Name;
use App\Product\Properties\Prices;
use App\Product\Properties\TeamId;
use App\Rate\RateValidationRules;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the product form, shared by create and update. A product's
 * name is unique in the team, archived ones included. Prices are typed in
 * dollars, as on rates (RateValidationRules::prepare).
 */
final class ProductValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?Product $current = null): array
    {
        return [
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($request),
                Rule::unique(Table::Products->value, Name::getDatabaseTableColumnName())
                    ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                    ->ignore($current?->getId()->toNullableString()),
            ],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Prices::getNameForValidation() => Prices::getValidationRulesForCreate($request),
            Prices::getNameForValidation().'.*' => ['array'],
            Prices::getNameForValidation().'.*.currency' => ['required', Rule::enum(CurrencyEnum::class), 'distinct'],
            Prices::getNameForValidation().'.*.price_in_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            Prices::getNameForValidation().'.*.cost_in_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ...RateValidationRules::messages(),
            Name::getNameForValidation().'.unique' => __('There is already a product with that name, or one in the archive.'),
            Prices::getNameForValidation().'.required' => __('Give the product a price.'),
            Prices::getNameForValidation().'.min' => __('Give the product a price.'),
            Prices::getNameForValidation().'.*.currency.distinct' => __('The product already has a price in this currency.'),
        ];
    }

    public static function prepare(FormRequest $request): void
    {
        RateValidationRules::prepare($request);
    }
}
