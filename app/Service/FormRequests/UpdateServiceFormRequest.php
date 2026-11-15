<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Service\Actions\FindService;
use App\Service\Actions\UpdateService;
use App\Service\Exceptions\CouldNotFindService;
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

final class UpdateServiceFormRequest extends FormRequest
{
    public Service $service;

    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->service = Service::new();
        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindService
     */
    public function authorize(): Response
    {
        $teamId = $this->user()?->current_team_id;
        $this->service = FindService::byIdForTeam((string) $this->route('service'), $teamId);

        if ($this->service->isNull()) {
            throw CouldNotFindService::notFound();
        }

        $this->customer = FindCustomer::byIdForTeam($this->service->getCustomerId()->toString(), $teamId);

        return Gate::authorize('update', $this->service);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
            Status::getNameForValidation() => Status::getValidationRulesForUpdate($this),
            Quantity::getNameForValidation() => Quantity::getValidationRulesForUpdate($this),
            UnitPriceInCents::getNameForValidation() => UnitPriceInCents::getValidationRulesForUpdate($this),
            BillingPeriodInMonths::getNameForValidation() => BillingPeriodInMonths::getValidationRulesForUpdate($this),
            StartsOn::getNameForValidation() => StartsOn::getValidationRulesForUpdate($this),
            EndsOn::getNameForValidation() => EndsOn::getValidationRulesForUpdate($this),
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForUpdate($this),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $this->customer->getId()->toString()),
            ],
            Note::getNameForValidation() => Note::getValidationRulesForUpdate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $service = UpdateService::fromService(
            $this->service
                ->setPropertyIfExists($this->validated()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $service->getName()->toString()])]);

        return to_route('customers.show', $this->customer->getId()->toString());
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
