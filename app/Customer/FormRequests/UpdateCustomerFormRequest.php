<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Actions\UpdateCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Customer\Properties\Abn;
use App\Customer\Properties\Email;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Status;
use App\Customer\Properties\Website;
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
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
            Abn::getNameForValidation() => Abn::getValidationRulesForUpdate($this),
            Email::getNameForValidation() => Email::getValidationRulesForUpdate($this),
            Phone::getNameForValidation() => Phone::getValidationRulesForUpdate($this),
            Website::getNameForValidation() => Website::getValidationRulesForUpdate($this),
            Status::getNameForValidation() => Status::getValidationRulesForUpdate($this),
            Notes::getNameForValidation() => Notes::getValidationRulesForUpdate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $customer = UpdateCustomer::fromCustomer($this->customer->setPropertyIfExists($this->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.show', $customer->getId()->toString());
    }
}
