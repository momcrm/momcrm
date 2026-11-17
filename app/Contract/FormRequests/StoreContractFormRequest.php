<?php

declare(strict_types=1);

namespace App\Contract\FormRequests;

use App\Contract\Actions\CreateContract;
use App\Contract\Contract;
use App\Contract\ContractValidationRules;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
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

final class StoreContractFormRequest extends FormRequest
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

        return Gate::authorize('create', Contract::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...ContractValidationRules::for($this),
            ...CustomFieldValueRules::for($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Contract),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContractValidationRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Contract);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $contract = CreateContract::fromContract(Contract::new()
            ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
            ->setCustomFields(CustomFieldValueRules::merge($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Contract, new CustomFieldValues(null), $validated[CustomFieldValues::getNameForValidation()] ?? []))
            ->setTeamId($this->customer->getTeamId())
            ->setCustomerId($this->customer->getId()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title added.', ['title' => $contract->getTitle()->toString()])]);

        return back();
    }
}
