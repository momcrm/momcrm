<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Actions\SetMainContactAndLocation;
use App\Customer\Actions\UpdateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerValidationRules;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class UpdateCustomerFormRequest extends FormRequest
{
    public const string MAIN_CONTACT_ID = 'main_contact_id';

    public const string MAIN_LOCATION_ID = 'main_location_id';

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
        $customerId = $this->customer->getId()->toNullableString();

        return [
            ...CustomerValidationRules::for($this, (string) $this->user()?->current_team_id, $this->customer),
            ...CustomFieldValueRules::for((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Customer, $this->customer->getCustomFields()),
            self::MAIN_CONTACT_ID => ['sometimes', 'nullable', 'uuid', Rule::exists(Table::Contacts->value, 'id')->where('customer_id', $customerId)],
            self::MAIN_LOCATION_ID => ['sometimes', 'nullable', 'uuid', Rule::exists(Table::Locations->value, 'id')->where('customer_id', $customerId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...CustomerValidationRules::messages(),
            self::MAIN_CONTACT_ID.'.exists' => __('Choose one of this customer\'s contacts.'),
            self::MAIN_LOCATION_ID.'.exists' => __('Choose one of this customer\'s locations.'),
        ];
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
        $customer = DB::transaction(function (): Customer {
            $validated = $this->validated();
            $customFields = CustomFieldValueRules::merge($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Customer, $this->customer->getCustomFields(), $validated[CustomFieldValues::getNameForValidation()] ?? []);
            $customer = UpdateCustomer::fromCustomer(
                $this->customer
                    ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                    ->setCustomFields($customFields),
            );

            if ($this->has([self::MAIN_CONTACT_ID, self::MAIN_LOCATION_ID])) {
                SetMainContactAndLocation::forCustomer($customer, $this->string(self::MAIN_CONTACT_ID)->toString() ?: null, $this->string(self::MAIN_LOCATION_ID)->toString() ?: null);
            }

            return $customer;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.edit', $customer->getId()->toString());
    }
}
