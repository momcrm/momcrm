<?php

declare(strict_types=1);

namespace App\Contact\FormRequests;

use App\Contact\Actions\FindContact;
use App\Contact\Actions\UpdateContact;
use App\Contact\Contact;
use App\Contact\Exceptions\CouldNotFindContact;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Email;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\LastName;
use App\Contact\Properties\MobilePhone;
use App\Contact\Properties\Position;
use App\Contact\Properties\ReceivesInvoices;
use App\Contact\Properties\WorkPhone;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class UpdateContactFormRequest extends FormRequest
{
    public Contact $contact;

    public function __construct()
    {
        parent::__construct();

        $this->contact = Contact::new();
    }

    /**
     * @throws CouldNotFindContact
     */
    public function authorize(): Response
    {
        $this->contact = FindContact::byIdForTeam((string) $this->route('contact'), $this->user()?->current_team_id);

        if ($this->contact->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return Gate::authorize('update', $this->contact);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            CustomerId::getNameForValidation() => [
                ...CustomerId::getValidationRulesForUpdate($this),
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $this->user()?->current_team_id),
            ],
            FirstName::getNameForValidation() => FirstName::getValidationRulesForUpdate($this),
            LastName::getNameForValidation() => LastName::getValidationRulesForUpdate($this),
            Email::getNameForValidation() => Email::getValidationRulesForUpdate($this),
            Position::getNameForValidation() => Position::getValidationRulesForUpdate($this),
            WorkPhone::getNameForValidation() => WorkPhone::getValidationRulesForUpdate($this),
            MobilePhone::getNameForValidation() => MobilePhone::getValidationRulesForUpdate($this),
            IsPrimary::getNameForValidation() => IsPrimary::getValidationRulesForUpdate($this),
            ReceivesInvoices::getNameForValidation() => ReceivesInvoices::getValidationRulesForUpdate($this),
            ...CustomFieldValueRules::for((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Contact, $this->contact->getCustomFields()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required' => __('Choose the customer this contact works for.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer this contact works for.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Contact);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $customFields = CustomFieldValueRules::merge($this->contact->getTeamId()->toString(), CustomFieldTargetEnum::Contact, $this->contact->getCustomFields(), $validated[CustomFieldValues::getNameForValidation()] ?? []);
        $contact = UpdateContact::fromContact(
            $this->contact
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                ->setCustomFields($customFields)
                ->setIsPrimary($this->boolean(IsPrimary::getNameForValidation()))
                ->setReceivesInvoices($this->boolean(ReceivesInvoices::getNameForValidation())),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $contact->getFullName()])]);

        return to_route('customers.contacts', $contact->getCustomerId()->toString());
    }
}
