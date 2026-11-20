<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Contact\ContactRecipients;
use App\Payment\Actions\FindPayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\Notifications\PaymentReceiptNotification;
use App\Payment\Payment;
use App\Payment\PaymentDocument;
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
 * Emails a payment's receipt to some of its customer's contacts.
 */
final class SendPaymentReceiptFormRequest extends FormRequest
{
    public Payment $payment;

    public function __construct()
    {
        parent::__construct();

        $this->payment = Payment::new();
    }

    /**
     * @throws CouldNotFindPayment
     */
    public function authorize(): Response
    {
        $this->payment = FindPayment::byIdForTeam((string) $this->route('payment'), $this->user()?->current_team_id);

        if ($this->payment->isNull()) {
            throw CouldNotFindPayment::notFound();
        }

        return Gate::authorize('update', $this->payment);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ContactRecipients::rules();
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
        return [fn (Validator $validator) => ContactRecipients::check($validator, $this->payment->getCustomerId()->toString(), $this->payment->getTeamId()->toString())];
    }

    public function process(): RedirectResponse
    {
        $emails = ContactRecipients::emails(
            Types::stringList($this->validated(ContactRecipients::FIELD)),
            $this->payment->getCustomerId()->toString(),
            $this->payment->getTeamId()->toString(),
        );

        Notification::route('mail', $emails)->notify(new PaymentReceiptNotification(PaymentDocument::for($this->payment)));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Receipt :reference sent to :emails.', [
            'reference' => $this->payment->getReferenceNumber(),
            'emails' => implode(', ', $emails),
        ])]);

        return back();
    }
}
