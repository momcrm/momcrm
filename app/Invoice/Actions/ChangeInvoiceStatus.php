<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Payment\Actions\ApplyCustomerCredit;
use App\Payment\Actions\FindPayment;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves an invoice from draft to sent, or voids it, stamping when it was
 * sent. A sent invoice is paid from the customer's credit straight away; a
 * voided one gives its payments back as credit. Payments make it paid
 * (SyncInvoicePaymentStatus).
 */
final readonly class ChangeInvoiceStatus
{
    public function __construct(
        private Invoice $invoice,
        private InvoiceStatusEnum $status,
    ) {}

    public function __invoke(): Invoice
    {
        if (! $this->invoice->getStatus()->toEnum()->canBecome($this->status)) {
            throw new InvalidArgumentException("A {$this->invoice->getStatus()->toString()} invoice cannot become {$this->status->value}.");
        }

        return DB::transaction(function (): Invoice {
            $this->invoice->setStatus($this->status);

            match ($this->status) {
                InvoiceStatusEnum::Sent => $this->invoice->setSentAt(CarbonImmutable::now()),
                InvoiceStatusEnum::Void => $this->releasePayments(),
                default => null,
            };

            UpdateInvoice::fromInvoice($this->invoice);

            if ($this->status === InvoiceStatusEnum::Sent) {
                ApplyCustomerCredit::toInvoice($this->invoice);
            }

            return FindInvoice::byIdForTeam($this->invoice->getId()->toString(), $this->invoice->getTeamId()->toString());
        });
    }

    /**
     * A voided invoice isn't owed, so the payments applied to it come off
     * and become the customer's credit again.
     */
    private function releasePayments(): void
    {
        FindPayment::allocationsForInvoice($this->invoice->getId()->toString(), $this->invoice->getTeamId()->toString())
            ->each(fn (PaymentAllocation $allocation): ?bool => PaymentAllocationEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($allocation->getId()->toString())
                ?->delete());
    }

    public static function to(Invoice $invoice, InvoiceStatusEnum $status): Invoice
    {
        return new self($invoice, $status)->handle();
    }

    public function handle(): Invoice
    {
        return $this->__invoke();
    }

    public function execute(): Invoice
    {
        return $this->__invoke();
    }
}
