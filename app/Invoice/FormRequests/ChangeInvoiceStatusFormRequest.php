<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Invoice\Actions\ChangeInvoiceStatus;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
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
    public function authorize(): bool
    {
        $this->invoice = FindInvoice::byIdForTeam((string) $this->route('invoice'), $this->user()?->current_team_id);

        if ($this->invoice->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        return true;
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

        $invoice = ChangeInvoiceStatus::to($this->invoice, $status);

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($status) {
            InvoiceStatusEnum::Sent => __(':reference marked as sent.', ['reference' => $invoice->getReference()]),
            InvoiceStatusEnum::Paid => __(':reference paid. Nice.', ['reference' => $invoice->getReference()]),
            default => __(':reference voided.', ['reference' => $invoice->getReference()]),
        }]);

        return to_route('invoices.show', $invoice->getId()->toString());
    }
}
