<?php

declare(strict_types=1);

namespace App\Contact\FormRequests;

use App\Contact\Actions\CreateContact;
use App\Contact\Contact;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Email;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\LastName;
use App\Contact\Properties\MobilePhone;
use App\Contact\Properties\Position;
use App\Contact\Properties\WorkPhone;
use App\Shared\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StoreContactFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Contact::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            CustomerId::getNameForValidation() => [
                ...CustomerId::getValidationRulesForCreate($this),
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $this->user()?->current_team_id),
            ],
            FirstName::getNameForValidation() => FirstName::getValidationRulesForCreate($this),
            LastName::getNameForValidation() => LastName::getValidationRulesForCreate($this),
            Email::getNameForValidation() => Email::getValidationRulesForCreate($this),
            Position::getNameForValidation() => Position::getValidationRulesForCreate($this),
            WorkPhone::getNameForValidation() => WorkPhone::getValidationRulesForCreate($this),
            MobilePhone::getNameForValidation() => MobilePhone::getValidationRulesForCreate($this),
            IsPrimary::getNameForValidation() => IsPrimary::getValidationRulesForCreate($this),
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
        $contact = CreateContact::fromContact(
            Contact::new()
                ->setPropertyIfExists($this->validated())
                ->setIsPrimary($this->boolean(IsPrimary::getNameForValidation()))
                ->setTeamId($this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $contact->getFullName()])]);

        return to_route('customers.contacts', $contact->getCustomerId()->toString());
    }
}
