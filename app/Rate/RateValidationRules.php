<?php

declare(strict_types=1);

namespace App\Rate;

use App\Rate\Actions\FindRate;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Properties\Description;
use App\Rate\Properties\Name;
use App\Rate\Properties\ParentId;
use App\Rate\Properties\Prices;
use App\Rate\Properties\ProratesFirstMonth;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the rate form, shared by create and update. As in Amphora, a
 * rate's name need only differ from the rates beside it under the same
 * parent, archived ones included.
 */
final class RateValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?Rate $current = null): array
    {
        $parentId = $request->has(ParentId::getNameForValidation())
            ? $request->input(ParentId::getNameForValidation())
            : $current?->getParentId()->toNullableString();

        return [
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($request),
                Rule::unique(Table::Rates->value, Name::getDatabaseTableColumnName())
                    ->where(Properties\TeamId::getDatabaseTableColumnName(), $teamId)
                    ->where(ParentId::getDatabaseTableColumnName(), is_string($parentId) ? $parentId : null)
                    ->ignore($current?->getId()->toNullableString()),
            ],
            ParentId::getNameForValidation() => ['nullable', 'uuid', self::canGoUnder($teamId, $current)],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Prices::getNameForValidation() => Prices::getValidationRulesForCreate($request),
            Prices::getNameForValidation().'.*' => ['array'],
            Prices::getNameForValidation().'.*.currency' => ['required', Rule::enum(CurrencyEnum::class), 'distinct'],
            Prices::getNameForValidation().'.*.price_in_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            Prices::getNameForValidation().'.*.cost_in_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
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
            Name::getNameForValidation().'.unique' => __('There is already a rate with that name here, or one in the archive.'),
            Prices::getNameForValidation().'.required' => __('Give the rate a price.'),
            Prices::getNameForValidation().'.min' => __('Give the rate a price.'),
            Prices::getNameForValidation().'.*.currency.required' => __('Choose a currency.'),
            Prices::getNameForValidation().'.*.currency.distinct' => __('The rate already has a price in this currency.'),
            Prices::getNameForValidation().'.*.price_in_cents.required' => __('Give a price.'),
            Prices::getNameForValidation().'.*.price_in_cents.integer' => __('Give a price.'),
            Prices::getNameForValidation().'.*.price_in_cents.min' => __('A price can\'t be negative.'),
            Prices::getNameForValidation().'.*.cost_in_cents.min' => __('A cost can\'t be negative.'),
        ];
    }

    /**
     * A parent is one of the team's rates that is still offered (or the one
     * the rate is already under), and never the rate itself or a rate under
     * it, which would make a loop.
     *
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private static function canGoUnder(string $teamId, ?Rate $current): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($teamId, $current): void {
            if (! is_string($value)) {
                return;
            }

            $rates = FindRate::listForTeam($teamId, withArchived: true);
            $parent = $rates->first(fn (Rate $rate): bool => $rate->getId()->toString() === $value);

            if ($parent->isNull()) {
                $fail(__('Choose one of your rates.'));

                return;
            }

            if ($parent->isArchived() && $value !== $current?->getParentId()->toNullableString()) {
                $fail(__(':name is archived. Choose a rate that is still offered.', ['name' => $parent->getName()->toString()]));

                return;
            }

            $currentId = $current?->getId()->toNullableString();

            if ($currentId !== null && ($value === $currentId || $rates->isUnder($value, $currentId))) {
                $fail(__('A rate can\'t go under itself or a rate under it.'));
            }
        };
    }

    /**
     * Each price is typed in dollars, with its currency, and stored in cents.
     */
    public static function prepare(FormRequest $request): void
    {
        $prices = $request->input(Prices::getNameForValidation());

        if (! is_array($prices)) {
            return;
        }

        $request->merge([Prices::getNameForValidation() => array_values(array_map(function (mixed $price): mixed {
            if (! is_array($price)) {
                return $price;
            }

            return [
                'currency' => is_string($price['currency'] ?? null) ? strtoupper($price['currency']) : ($price['currency'] ?? null),
                'price_in_cents' => self::cents($price['price'] ?? null),
                'cost_in_cents' => self::cents($price['cost'] ?? null),
            ];
        }, $prices))]);
    }

    private static function cents(mixed $amount): mixed
    {
        return match (true) {
            is_numeric($amount) => (int) round(((float) $amount) * 100),
            $amount === '' => null,
            default => $amount,
        };
    }
}
