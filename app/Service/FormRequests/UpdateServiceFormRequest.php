<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Service\Actions\FindService;
use App\Service\Actions\MakeServiceShortName;
use App\Service\Actions\UpdateService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Service;
use App\Service\ServiceValidationRules;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Saves a service's details. Its rate, status and archive have their own
 * actions, so each change shows in the service's activity.
 */
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
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ...ServiceValidationRules::for($this, $this->customer->getId()->toString(), $this->service->getId()->toString()),
            ...CustomFieldValueRules::for($this->service->getTeamId()->toString(), CustomFieldTargetEnum::Service, $this->service->getCustomFields()),
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
        return CustomFieldValueRules::attributes($this->service->getTeamId()->toString(), CustomFieldTargetEnum::Service);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $this->service
            ->setCustomFields(CustomFieldValueRules::merge($this->service->getTeamId()->toString(), CustomFieldTargetEnum::Service, $this->service->getCustomFields(), $validated[CustomFieldValues::getNameForValidation()] ?? []))
            ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())));

        if (blank($this->service->getShortName()->toNullableString())) {
            $this->service->setShortName(MakeServiceShortName::for($this->service->getName()->toString(), $this->customer->getId()->toString(), $this->service->getId()->toString()));
        }

        $service = UpdateService::fromService($this->service);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $service->getName()->toString()])]);

        return back();
    }
}
