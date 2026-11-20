<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Invoice\Actions\SyncInvoicePaymentStatus;
use App\Payment\Payment;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\PaymentAllocation\Properties\AmountInCents;
use App\PaymentAllocation\Properties\Id as AllocationId;
use App\PaymentAllocation\Properties\InvoiceId;
use App\PaymentAllocation\Properties\PaymentId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Applies more of a payment to invoices: an invoice it already paid part of
 * gets the extra added to that allocation. Invoices it pays off become
 * paid. The amounts must already have been checked (PaymentAllocationRules).
 */
final readonly class AllocatePayment
{
    /**
     * @param  array<string, int>  $allocations  cents to apply, keyed by invoice id
     */
    public function __construct(
        private Payment $payment,
        private array $allocations,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $teamId = $this->payment->getTeamId()->toString();

            foreach ($this->allocations as $invoiceId => $amountInCents) {
                $existing = PaymentAllocationEloquentModel::query()
                    ->withoutGlobalScope(CurrentTeamScope::class)
                    ->where(PaymentId::getDatabaseTableColumnName(), $this->payment->getId()->toString())
                    ->where(InvoiceId::getDatabaseTableColumnName(), (string) $invoiceId)
                    ->first();

                if ($existing !== null) {
                    $existing->update([AmountInCents::getDatabaseTableColumnName() => $existing->amount_in_cents + $amountInCents]);

                    continue;
                }

                PaymentAllocationEloquentModel::query()->create(
                    PaymentAllocation::new()
                        ->setId(AllocationId::generate())
                        ->setTeamId($teamId)
                        ->setPaymentId($this->payment->getId()->toString())
                        ->setInvoiceId((string) $invoiceId)
                        ->setAmountInCents($amountInCents)
                        ->forDatabaseCreate(),
                );
            }

            SyncInvoicePaymentStatus::forInvoices(array_map(strval(...), array_keys($this->allocations)), $teamId);
        });
    }

    /**
     * @param  array<string, int>  $allocations  cents to apply, keyed by invoice id
     */
    public static function toInvoices(Payment $payment, array $allocations): void
    {
        new self($payment, $allocations)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
