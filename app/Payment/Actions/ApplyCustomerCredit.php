<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Pays a sent invoice from its customer's credit, as Amphora's "pay using
 * credit" does: their payments with money not yet applied are used oldest
 * first until the invoice is paid or the credit runs out.
 */
final readonly class ApplyCustomerCredit
{
    public function __construct(
        private Invoice $invoice,
    ) {}

    /**
     * @return int the cents applied
     */
    public function __invoke(): int
    {
        return DB::transaction(function (): int {
            $teamId = $this->invoice->getTeamId()->toString();
            $invoice = FindInvoice::byIdsForTeam([$this->invoice->getId()->toString()], $teamId)[$this->invoice->getId()->toString()] ?? null;

            if ($invoice === null || ! $invoice->canTakePayments()) {
                return 0;
            }

            $owing = $invoice->balanceAfter($invoice->totals());
            $applied = 0;

            foreach (FindPayment::withCreditForCustomer($invoice->getCustomerId()->toString(), $teamId) as $payment) {
                if ($owing - $applied <= 0) {
                    break;
                }

                if ($payment->getCurrency()->toEnum() !== $invoice->getCurrency()->toEnum()) {
                    continue;
                }

                $amount = min($payment->getUnallocatedInCents(), $owing - $applied);
                AllocatePayment::toInvoices($payment, [$invoice->getId()->toString() => $amount]);
                $applied += $amount;
            }

            return $applied;
        });
    }

    /**
     * @return int the cents applied
     */
    public static function toInvoice(Invoice $invoice): int
    {
        return new self($invoice)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }
}
