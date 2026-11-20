<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Contact\ContactRecipients;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\CustomerStatement;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Customer\Notifications\CustomerStatementNotification;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Emails a customer's statement for a period to some of their contacts.
 */
final class SendCustomerStatementFormRequest extends FormRequest
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

        return Gate::authorize('update', $this->customer);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            ...ContactRecipients::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContactRecipients::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => ContactRecipients::check($validator, $this->customer->getId()->toString(), $this->customer->getTeamId()->toString())];
    }

    public function process(): RedirectResponse
    {
        [$from, $to] = CustomerStatement::periodFrom($this);
        $emails = ContactRecipients::emails(
            Types::stringList($this->validated(ContactRecipients::FIELD)),
            $this->customer->getId()->toString(),
            $this->customer->getTeamId()->toString(),
        );

        Notification::route('mail', $emails)->notify(new CustomerStatementNotification(CustomerStatement::for($this->customer, $from, $to)));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Statement sent to :emails.', ['emails' => implode(', ', $emails)])]);

        return back();
    }
}
