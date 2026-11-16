<?php

declare(strict_types=1);

namespace App\Secret;

use App\Secret\Properties\AssetId;
use App\Secret\Properties\Description;
use App\Secret\Properties\Title;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of the secret form, shared by create and update.
 */
final class SecretValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, ?string $customerId, bool $valuesRequired = true): array
    {
        return [
            Title::getNameForValidation() => Title::getValidationRulesForCreate($request),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            AssetId::getNameForValidation() => [
                ...AssetId::getValidationRulesForCreate($request),
                Rule::exists(Table::Assets->value, 'id')->where('customer_id', $customerId),
            ],
            ...SecretValues::rules($valuesRequired),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ...SecretValues::messages(),
            AssetId::getNameForValidation().'.exists' => __('Choose one of this customer\'s assets.'),
        ];
    }
}
