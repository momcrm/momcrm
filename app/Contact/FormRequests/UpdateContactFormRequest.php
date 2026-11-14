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
use App\Contact\Properties\WorkPhone;
use App\Shared\Table;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
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
    public function authorize(): bool
    {
        $this->contact = FindContact::byIdForTeam((string) $this->route('contact'), $this->user()?->current_team_id);

        if ($this->contact->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
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

    public function process(): RedirectResponse
    {
        $contact = UpdateContact::fromContact(
            $this->contact
                ->setPropertyIfExists($this->validated())
                ->setIsPrimary($this->boolean(IsPrimary::getNameForValidation())),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $contact->getFullName()])]);

        return to_route('customers.show', $contact->getCustomerId()->toString());
    }
}
