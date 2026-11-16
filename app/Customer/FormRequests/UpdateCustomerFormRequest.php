<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Actions\UpdateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerValidationRules;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateCustomerFormRequest extends FormRequest
{
    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $this->customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($this->customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Gate::authorize('update', $this->customer);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return CustomerValidationRules::for($this, (string) $this->user()?->current_team_id, $this->customer);
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
        $customer = UpdateCustomer::fromCustomer($this->customer->setPropertyIfExists($this->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.show', $customer->getId()->toString());
    }
}
