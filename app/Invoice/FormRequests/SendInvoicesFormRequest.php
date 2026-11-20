<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Contact\ContactRecipients;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Jobs\EmailInvoiceJob;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Emails several invoices at once, each to its customer's contacts who get
 * invoices (else their primary contact), one email per invoice, queued.
 * Drafts are marked sent. Invoices whose customer has nobody to send to are
 * left as they are and named.
 */
final class SendInvoicesFormRequest extends FormRequest
{
    public const string INVOICE_IDS = 'invoice_ids';

    public const int MAX = 200;

    /** @var array<string, Invoice> */
    public array $invoices = [];

    /**
     * @throws CouldNotFindInvoice
     */
    public function authorize(): Response
    {
        $input = $this->input(self::INVOICE_IDS);
        $ids = array_values(array_unique(array_filter(
            is_array($input) ? $input : [],
            fn (mixed $id): bool => is_string($id) && Str::isUuid($id),
        )));

        if ($ids === [] || count($ids) > self::MAX) {
            return Response::allow();
        }

        $this->invoices = FindInvoice::byIdsForTeam($ids, (string) $this->user()?->current_team_id);

        if (count($this->invoices) !== count($ids)) {
            throw CouldNotFindInvoice::notFound();
        }

        foreach ($this->invoices as $invoice) {
            Gate::authorize($invoice->getStatus()->toEnum() === InvoiceStatusEnum::Draft ? 'changeStatus' : 'update', $invoice);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            self::INVOICE_IDS => ['required', 'array', 'min:1', 'max:'.self::MAX],
            self::INVOICE_IDS.'.*' => ['uuid', 'distinct'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->invoices as $invoice) {
                if ($invoice->getStatus()->toEnum() === InvoiceStatusEnum::Void) {
                    $validator->errors()->add(self::INVOICE_IDS, __(':reference is voided and can’t be sent.', ['reference' => $invoice->getReference()]));

                    return;
                }
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $sending = 0;
        $nobody = [];

        foreach ($this->invoices as $invoice) {
            $emails = ContactRecipients::invoiceEmails($invoice->getCustomerId()->toString(), $teamId);

            if ($emails === []) {
                $nobody[] = $invoice->getCustomerId()->toString();

                continue;
            }

            EmailInvoiceJob::dispatch($invoice->getId()->toString(), $teamId, $emails);
            $sending++;
        }

        $message = trans_choice('Sending :count invoice.|Sending :count invoices.', $sending);

        if ($nobody !== []) {
            $names = FindCustomer::byIdsForTeam($teamId, array_values(array_unique($nobody)))
                ->map(fn (Customer $customer): string => $customer->getName()->toString())
                ->implode(', ');

            $message .= ' '.__('Not sent, as no contact with an email gets invoices: :names.', ['names' => $names]);
        }

        Inertia::flash('toast', ['type' => $nobody === [] ? 'success' : 'warning', 'message' => $message]);

        return back();
    }
}
