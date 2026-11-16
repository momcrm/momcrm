<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Service\Actions\CreateService;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\EndsOn;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\StartsOn;
use App\Service\Properties\Status;
use App\Service\Properties\UnitPriceInCents;
use App\Service\Service;
use App\Shared\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StoreServiceFormRequest extends FormRequest
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

        return Gate::authorize('create', Service::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($this),
            Quantity::getNameForValidation() => Quantity::getValidationRulesForCreate($this),
            UnitPriceInCents::getNameForValidation() => UnitPriceInCents::getValidationRulesForCreate($this),
            BillingPeriodInMonths::getNameForValidation() => BillingPeriodInMonths::getValidationRulesForCreate($this),
            StartsOn::getNameForValidation() => StartsOn::getValidationRulesForCreate($this),
            EndsOn::getNameForValidation() => EndsOn::getValidationRulesForCreate($this),
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForCreate($this),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $this->customer->getId()->toString()),
            ],
            Note::getNameForValidation() => Note::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $service = CreateService::fromService(
            Service::new()
                ->setPropertyIfExists($this->validated())
                ->setCustomerId($this->customer->getId())
                ->setTeamId($this->customer->getTeamId()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $service->getName()->toString()])]);

        return to_route('customers.services', $this->customer->getId()->toString());
    }

    /**
     * Amounts are typed in dollars and stored in cents.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('unit_price')) {
            $amount = $this->input('unit_price');
            $this->merge([UnitPriceInCents::getNameForValidation() => is_numeric($amount) ? (int) round(((float) $amount) * 100) : $amount]);
        }
    }
}
