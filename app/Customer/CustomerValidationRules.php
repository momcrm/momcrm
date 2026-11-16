<?php

declare(strict_types=1);

namespace App\Customer;

use App\Choice\ChoiceListEnum;
use App\Customer\Properties\Abn;
use App\Customer\Properties\BillingType;
use App\Customer\Properties\Email;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Prefix;
use App\Customer\Properties\Status;
use App\Customer\Properties\TypeId;
use App\Customer\Properties\Website;
use App\Shared\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the customer form, shared by create and update.
 */
final class CustomerValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, string $teamId, ?Customer $current = null): array
    {
        $currentId = $current?->getId()->toNullableString();
        $currentTypeId = $current?->getTypeId()->toNullableString();

        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($request),
            TypeId::getNameForValidation() => [
                ...TypeId::getValidationRulesForCreate($request),
                Rule::exists(Table::Choices->value, 'id')
                    ->where('team_id', $teamId)
                    ->where('list', ChoiceListEnum::CustomerType->value)
                    ->where(fn (Builder $query) => $query->whereNull('archived_at')->orWhere('id', $currentTypeId)),
            ],
            Prefix::getNameForValidation() => [
                ...Prefix::getValidationRulesForCreate($request),
                Rule::unique(Table::Customers->value, Prefix::getDatabaseTableColumnName())
                    ->where('team_id', $teamId)
                    ->ignore($currentId),
            ],
            Abn::getNameForValidation() => Abn::getValidationRulesForCreate($request),
            Email::getNameForValidation() => Email::getValidationRulesForCreate($request),
            Phone::getNameForValidation() => Phone::getValidationRulesForCreate($request),
            Website::getNameForValidation() => Website::getValidationRulesForCreate($request),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($request),
            BillingType::getNameForValidation() => BillingType::getValidationRulesForCreate($request),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($request),
        ];
    }

    /**
     * The prefix in capitals, so uniqueness is checked the way it is stored.
     *
     * @return array<string, mixed>
     */
    public static function prepare(FormRequest $request): array
    {
        if (! $request->has(Prefix::getNameForValidation())) {
            return [];
        }

        return [Prefix::getNameForValidation() => Prefix::normalise($request->input(Prefix::getNameForValidation()))];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            TypeId::getNameForValidation().'.exists' => __('Choose one of your team\'s customer types.'),
            Prefix::getNameForValidation().'.unique' => __('Another customer already uses this prefix.'),
            Prefix::getNameForValidation().'.regex' => __('Use letters only.'),
        ];
    }
}
