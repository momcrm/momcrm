<?php

declare(strict_types=1);

namespace App\Contract;

use App\Contract\Properties\Description;
use App\Contract\Properties\EndsOn;
use App\Contract\Properties\Notes;
use App\Contract\Properties\PurchaseOrder;
use App\Contract\Properties\SignedOn;
use App\Contract\Properties\StartsOn;
use App\Contract\Properties\Status;
use App\Contract\Properties\Title;
use App\Contract\Properties\Type;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The fields of the contract form, shared by create and update.
 */
final class ContractValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request): array
    {
        return [
            Title::getNameForValidation() => Title::getValidationRulesForCreate($request),
            Type::getNameForValidation() => Type::getValidationRulesForCreate($request),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($request),
            SignedOn::getNameForValidation() => SignedOn::getValidationRulesForCreate($request),
            StartsOn::getNameForValidation() => StartsOn::getValidationRulesForCreate($request),
            EndsOn::getNameForValidation() => EndsOn::getValidationRulesForCreate($request),
            PurchaseOrder::getNameForValidation() => PurchaseOrder::getValidationRulesForCreate($request),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($request),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            EndsOn::getNameForValidation().'.after_or_equal' => __('The end date can\'t be before the start date.'),
        ];
    }
}
