<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Contact\ContactRecipients;
use App\Invoice\Actions\EmailInvoice;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Emails an invoice to some of its customer's contacts. Sending a draft
 * marks it sent, so it also needs the right to change an invoice's status.
 */
final class SendInvoiceFormRequest extends FormRequest
{
    public Invoice $invoice;

    public function __construct()
    {
        parent::__construct();

        $this->invoice = Invoice::new();
    }

    /**
     * @throws CouldNotFindInvoice
     */
    public function authorize(): Response
    {
        $this->invoice = FindInvoice::byIdForTeam((string) $this->route('invoice'), $this->user()?->current_team_id);

        if ($this->invoice->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        return $this->invoice->getStatus()->toEnum() === InvoiceStatusEnum::Draft
            ? Gate::authorize('changeStatus', $this->invoice)
            : Gate::authorize('update', $this->invoice);
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
        return [function (Validator $validator): void {
            if ($this->invoice->getStatus()->toEnum() === InvoiceStatusEnum::Void) {
                $validator->errors()->add(ContactRecipients::FIELD, __('A voided invoice can’t be sent.'));

                return;
            }

            ContactRecipients::check($validator, $this->invoice->getCustomerId()->toString(), $this->invoice->getTeamId()->toString());
        }];
    }

    public function process(): RedirectResponse
    {
        $emails = ContactRecipients::emails(
            Types::stringList($this->validated(ContactRecipients::FIELD)),
            $this->invoice->getCustomerId()->toString(),
            $this->invoice->getTeamId()->toString(),
        );

        EmailInvoice::to($this->invoice, $emails);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference sent to :emails.', [
            'reference' => $this->invoice->getReference(),
            'emails' => implode(', ', $emails),
        ])]);

        return back();
    }
}
