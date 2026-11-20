<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Invoice\Actions\SyncInvoicePaymentStatus;
use App\Payment\Payment;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a payment and takes it off the invoices it paid, which are owed
 * again: a paid invoice goes back to sent.
 */
final readonly class DeletePayment
{
    public function __construct(
        private Payment $payment,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $allocations = FindPayment::allocationsFor($this->payment);
            $invoiceIds = array_values($allocations->map(fn (PaymentAllocation $allocation): string => $allocation->getInvoiceId()->toString())->all());

            // One at a time, so each removal is audited.
            PaymentAllocationEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->whereKey(array_values($allocations->map(fn (PaymentAllocation $allocation): string => $allocation->getId()->toString())->all()))
                ->get()
                ->each(fn (PaymentAllocationEloquentModel $model): ?bool => $model->delete());

            PaymentEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->payment->getId()->toString())
                ?->delete();

            SyncInvoicePaymentStatus::forInvoices($invoiceIds, $this->payment->getTeamId()->toString());
        });
    }

    public static function fromPayment(Payment $payment): void
    {
        new self($payment)->handle();
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
