<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\DeleteCustomer;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeleteCustomerFormRequest extends FormRequest
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
    public function authorize(): bool
    {
        $this->customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($this->customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        DeleteCustomer::fromCustomer($this->customer);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->customer->getName()->toString()])]);

        return to_route('customers.index');
    }
}
