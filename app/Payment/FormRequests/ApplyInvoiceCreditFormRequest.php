<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Payment\Actions\ApplyCustomerCredit;
use App\Payment\Payment;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Pays an invoice from its customer's credit.
 */
final class ApplyInvoiceCreditFormRequest extends FormRequest
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

        return Gate::authorize('create', Payment::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        if (! $this->invoice->canTakePayments()) {
            throw ValidationException::withMessages(['invoice' => __('Only a sent invoice can be paid.')]);
        }

        $applied = ApplyCustomerCredit::toInvoice($this->invoice);

        if ($applied === 0) {
            throw ValidationException::withMessages(['invoice' => __('This customer has no credit to apply.')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':amount of credit applied to :reference.', [
            'amount' => Number::currency($applied / 100, $this->invoice->getCurrency()->toEnum()->value),
            'reference' => $this->invoice->getReference(),
        ])]);

        return back();
    }
}
