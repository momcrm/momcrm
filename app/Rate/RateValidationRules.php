<?php

declare(strict_types=1);

namespace App\Rate;

use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Properties\CostInCents;
use App\Rate\Properties\Description;
use App\Rate\Properties\Name;
use App\Rate\Properties\PriceInCents;
use App\Rate\Properties\ProratesFirstMonth;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the rate form, shared by create and update.
 */
final class RateValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?string $ignoreId = null): array
    {
        return [
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($request),
                Rule::unique(Table::Rates->value, Name::getDatabaseTableColumnName())
                    ->where(Properties\TeamId::getDatabaseTableColumnName(), $teamId)
                    ->ignore($ignoreId),
            ],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            PriceInCents::getNameForValidation() => PriceInCents::getValidationRulesForCreate($request),
            CostInCents::getNameForValidation() => CostInCents::getValidationRulesForCreate($request),
            BillingPeriodInMonths::getNameForValidation() => BillingPeriodInMonths::getValidationRulesForCreate($request),
            ProratesFirstMonth::getNameForValidation() => ProratesFirstMonth::getValidationRulesForCreate($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('There is already a rate with that name, or one in the archive.'),
        ];
    }

    /**
     * Prices are typed in dollars and stored in cents.
     */
    public static function prepare(FormRequest $request): void
    {
        foreach (['price' => PriceInCents::getNameForValidation(), 'cost' => CostInCents::getNameForValidation()] as $input => $field) {
            if ($request->has($input)) {
                $amount = $request->input($input);
                $request->merge([$field => match (true) {
                    is_numeric($amount) => (int) round(((float) $amount) * 100),
                    $amount === '' => null,
                    default => $amount,
                }]);
            }
        }
    }
}
