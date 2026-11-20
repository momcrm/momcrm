<?php

declare(strict_types=1);

namespace App\Payment;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Payment\Actions\FindPayment;
use App\PaymentAllocation\PaymentAllocationCollection;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Illuminate\Support\Number;

/**
 * Everything printed on a payment's receipt: who it is from and for, how
 * much was paid and how, the invoices it paid, and the credit the customer
 * has left. The PDF and the email that sends it both use this.
 */
final readonly class PaymentDocument
{
    public function __construct(
        public Payment $payment,
        public PaymentAllocationCollection $allocations,
        public Customer $customer,
        public Team $team,
        public int $customerCreditInCents,
    ) {}

    public static function for(Payment $payment): self
    {
        $teamId = $payment->getTeamId()->toString();
        $customerId = $payment->getCustomerId()->toString();

        return new self(
            $payment,
            FindPayment::allocationsFor($payment),
            FindCustomer::byIdForTeam($customerId, $teamId),
            FindTeam::byId($teamId),
            FindPayment::creditForCustomers($teamId, [$customerId])[$customerId] ?? 0,
        );
    }

    /**
     * The file name the PDF downloads as, e.g. PAY-0042.pdf.
     */
    public function fileName(): string
    {
        return $this->payment->getReferenceNumber().'.pdf';
    }

    /**
     * An amount in the payment's currency, e.g. A$1,250.00.
     */
    public function money(int $cents): string
    {
        return (string) Number::currency($cents / 100, $this->payment->getCurrency()->toEnum()->value);
    }
}
