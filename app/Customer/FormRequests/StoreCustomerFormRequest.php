<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\CreateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
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
        return CustomerValidationRules::for($this, (string) $this->user()?->current_team_id);
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

    public function process(): RedirectResponse
    {
        $customer = CreateCustomer::fromCustomer(
            Customer::new()
                ->setPropertyIfExists($this->validated())
                ->setTeamId($this->user()?->current_team_id)
                ->setIsSample(false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.show', $customer->getId()->toString());
    }
}
