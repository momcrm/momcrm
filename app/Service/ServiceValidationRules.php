<?php

declare(strict_types=1);

namespace App\Service;

use App\Rate\Actions\FindRate;
use App\Rate\Properties\ArchivedAt as RateArchivedAt;
use App\Rate\Properties\TeamId as RateTeamId;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\ContactId;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\RateId;
use App\Service\Properties\ShortName;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The fields of the service form, shared by create and update. The rate is
 * chosen on create and changed with its own action afterwards.
 */
final class ServiceValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $customerId, ?string $ignoreServiceId = null): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($request),
            ShortName::getNameForValidation() => [
                ...ShortName::getValidationRulesForCreate($request),
                Rule::unique(Table::Services->value, ShortName::getDatabaseTableColumnName())
                    ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
                    ->ignore($ignoreServiceId),
            ],
            Quantity::getNameForValidation() => Quantity::getValidationRulesForCreate($request),
            BillingPeriodInMonths::getNameForValidation() => BillingPeriodInMonths::getValidationRulesForCreate($request),
            BillingStartsOn::getNameForValidation() => BillingStartsOn::getValidationRulesForCreate($request),
            EndsOn::getNameForValidation() => EndsOn::getValidationRulesForCreate($request),
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForCreate($request),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $customerId),
            ],
            ContactId::getNameForValidation() => [
                ...ContactId::getValidationRulesForCreate($request),
                Rule::exists(Table::Contacts->value, 'id')->where('customer_id', $customerId),
            ],
            AutoRenew::getNameForValidation() => AutoRenew::getValidationRulesForCreate($request),
            Note::getNameForValidation() => Note::getValidationRulesForCreate($request),
        ];
    }

    /**
     * A rate of the team's that is still offered, with a price in each
     * currency the services' customers are billed in.
     *
     * @param  list<CurrencyEnum>  $currencies
     * @return array<int, mixed>
     */
    public static function rate(FormRequest $request, string $teamId, array $currencies): array
    {
        return [...RateId::getValidationRulesForCreate($request), self::liveRateOf($teamId), self::pricedIn($teamId, $currencies)];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ShortName::getNameForValidation().'.unique' => __('This customer already has a service with that short name.'),
            ShortName::getNameForValidation().'.regex' => __('Use only letters, numbers and @ . _ -, as in a domain or email address.'),
            RateId::getNameForValidation().'.exists' => __('Choose one of your rates.'),
            EndsOn::getNameForValidation().'.after_or_equal' => __('It can\'t expire before billing starts.'),
        ];
    }

    /**
     * @param  list<CurrencyEnum>  $currencies
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private static function pricedIn(string $teamId, array $currencies): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($teamId, $currencies): void {
            $rate = FindRate::byIdForTeam(is_string($value) ? $value : null, $teamId);

            if ($rate->isNull()) {
                return;
            }

            $missing = array_values(array_filter($currencies, fn (CurrencyEnum $currency): bool => $rate->priceIn($currency) === null));

            if ($missing !== []) {
                $fail(__(':rate has no price in :currencies. Add one under Settings, Rates.', [
                    'rate' => $rate->getName()->toString(),
                    'currencies' => implode(', ', array_unique(array_map(fn (CurrencyEnum $currency): string => $currency->value, $missing))),
                ]));
            }
        };
    }

    private static function liveRateOf(string $teamId): Exists
    {
        return Rule::exists(Table::Rates->value, 'id')
            ->where(RateTeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNull(RateArchivedAt::getDatabaseTableColumnName());
    }
}
