<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Invoice\Actions\ChangeInvoiceStatus;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class ChangeInvoiceStatusFormRequest extends FormRequest
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

        return Gate::authorize('changeStatus', $this->invoice);
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
    public function process(InvoiceStatusEnum $status): RedirectResponse
    {
        $current = $this->invoice->getStatus()->toEnum();

        if (! $current->canBecome($status)) {
            throw ValidationException::withMessages([
                'status' => __('A :current invoice cannot be marked :next.', ['current' => mb_strtolower($current->label()), 'next' => mb_strtolower($status->label())]),
            ]);
        }

        $paidBefore = $this->invoice->getPaidInCents()->toInteger();
        $invoice = ChangeInvoiceStatus::to($this->invoice, $status);
        $money = fn (int $cents): string => (string) Number::currency($cents / 100, $invoice->getCurrency()->toEnum()->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => match (true) {
            $status === InvoiceStatusEnum::Sent && $invoice->getPaidInCents()->toInteger() > 0 => __(':reference marked as sent. :amount of credit was applied to it.', ['reference' => $invoice->getReference(), 'amount' => $money($invoice->getPaidInCents()->toInteger())]),
            $status === InvoiceStatusEnum::Sent => __(':reference marked as sent.', ['reference' => $invoice->getReference()]),
            $paidBefore > 0 => __(':reference voided. The :amount paid on it is now credit.', ['reference' => $invoice->getReference(), 'amount' => $money($paidBefore)]),
            default => __(':reference voided.', ['reference' => $invoice->getReference()]),
        }]);

        return back();
    }
}
