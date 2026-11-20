<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Actions;

use App\Invoice\Actions\SyncInvoicePaymentStatus;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Changes how much of a payment goes to an invoice, or with null takes it
 * off the invoice. Whatever comes off is the customer's credit, and the
 * invoice is paid or owed again to match.
 */
final readonly class ChangePaymentAllocation
{
    public function __construct(
        private PaymentAllocation $allocation,
        private ?int $amountInCents,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $model = PaymentAllocationEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->allocation->getId()->toString());

            if ($this->amountInCents === null) {
                $model?->delete();
            } else {
                $model?->update($this->allocation->setAmountInCents($this->amountInCents)->forDatabaseUpdate());
            }

            SyncInvoicePaymentStatus::forInvoices([$this->allocation->getInvoiceId()->toString()], $this->allocation->getTeamId()->toString());
        });
    }

    public static function to(PaymentAllocation $allocation, int $amountInCents): void
    {
        new self($allocation, $amountInCents)->handle();
    }

    public static function remove(PaymentAllocation $allocation): void
    {
        new self($allocation, null)->handle();
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
