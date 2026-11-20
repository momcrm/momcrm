<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Invoice\Actions\SyncInvoicePaymentStatus;
use App\Payment\Payment;
use App\Payment\PaymentEloquentModel;
use App\Payment\Properties\Id;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\PaymentAllocation\Properties\Id as AllocationId;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\Table;
use Illuminate\Support\Facades\DB;

/**
 * Records money received from a customer, numbered within its team, and
 * applies it to their invoices. Invoices it pays off become paid. The
 * allocations must already have been checked (PaymentAllocationRules).
 */
final readonly class RecordPayment
{
    /**
     * @param  array<string, int>  $allocations  cents applied, keyed by invoice id
     */
    public function __construct(
        private Payment $payment,
        private array $allocations,
    ) {}

    public function __invoke(): Payment
    {
        return DB::transaction(function (): Payment {
            $teamId = $this->payment->getTeamId()->toString();

            $this->payment
                ->setId(Id::generate())
                ->setNumber(NextTeamNumber::for(Table::Payments, $teamId));

            PaymentEloquentModel::query()->create($this->payment->forDatabaseCreate());

            foreach ($this->allocations as $invoiceId => $amountInCents) {
                PaymentAllocationEloquentModel::query()->create(
                    PaymentAllocation::new()
                        ->setId(AllocationId::generate())
                        ->setTeamId($teamId)
                        ->setPaymentId($this->payment->getId()->toString())
                        ->setInvoiceId($invoiceId)
                        ->setAmountInCents($amountInCents)
                        ->forDatabaseCreate(),
                );
            }

            SyncInvoicePaymentStatus::forInvoices(array_map(strval(...), array_keys($this->allocations)), $teamId);

            return FindPayment::byIdForTeam($this->payment->getId()->toString(), $teamId);
        });
    }

    /**
     * @param  array<string, int>  $allocations  cents applied, keyed by invoice id
     */
    public static function fromPayment(Payment $payment, array $allocations): Payment
    {
        return new self($payment, $allocations)->handle();
    }

    public function handle(): Payment
    {
        return $this->__invoke();
    }

    public function execute(): Payment
    {
        return $this->__invoke();
    }
}
