<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Service\Actions\CreateService;
use App\Service\Properties\RateId;
use App\Service\Properties\Status;
use App\Service\Service;
use App\Service\ServiceValidationRules;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
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
        $teamId = $this->customer->getTeamId()->toString();

        return [
            ...ServiceValidationRules::for($this, $this->customer->getId()->toString()),
            RateId::getNameForValidation() => ServiceValidationRules::rate($this, $teamId),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($this),
            ...CustomFieldValueRules::for($teamId, CustomFieldTargetEnum::Service),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ServiceValidationRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Service);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $service = CreateService::fromService(
            Service::new()
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                ->setCustomFields(CustomFieldValueRules::merge($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Service, new CustomFieldValues(null), $validated[CustomFieldValues::getNameForValidation()] ?? []))
                ->setCustomerId($this->customer->getId())
                ->setTeamId($this->customer->getTeamId()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $service->getName()->toString()])]);

        return to_route('customers.services', $this->customer->getId()->toString());
    }
}
