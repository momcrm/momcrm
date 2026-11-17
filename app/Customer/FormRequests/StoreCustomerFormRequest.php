<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\CreateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerValidationRules;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreCustomerFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Customer::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;

        return [
            ...CustomerValidationRules::for($this, $teamId),
            ...CustomFieldValueRules::for($teamId, CustomFieldTargetEnum::Customer),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CustomerValidationRules::messages();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(CustomerValidationRules::prepare($this));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Customer);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $customer = CreateCustomer::fromCustomer(
            Customer::new()
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                ->setCustomFields(CustomFieldValueRules::merge((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Customer, new CustomFieldValues(null), $validated[CustomFieldValues::getNameForValidation()] ?? []))
                ->setTeamId($this->user()?->current_team_id)
                ->setIsSample(false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.show', $customer->getId()->toString());
    }
}
