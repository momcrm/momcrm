<?php

declare(strict_types=1);

namespace App\BankRule;

use App\BankRule\Properties\Contains;
use App\BankRule\Properties\CustomerId;
use App\BankRule\Properties\Kind;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What a bank feed rule must have: its text, what it does, and for a match
 * rule one of the team's customers.
 */
final class BankRuleValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request): array
    {
        return [
            Kind::getNameForValidation() => Kind::getValidationRulesForCreate($request),
            Contains::getNameForValidation() => Contains::getValidationRulesForCreate($request),
            CustomerId::getNameForValidation() => [
                Rule::requiredIf($request->input(Kind::getNameForValidation()) === BankRuleKindEnum::Match->value),
                Rule::prohibitedIf($request->input(Kind::getNameForValidation()) === BankRuleKindEnum::Hide->value),
                'nullable',
                'uuid',
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $request->user()?->current_team_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required' => __('Choose the customer these deposits are from.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose one of your customers.'),
            Contains::getNameForValidation().'.min' => __('Use at least 3 characters, so the rule doesn’t catch everything.'),
        ];
    }
}
