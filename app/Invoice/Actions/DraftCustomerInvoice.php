<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Customer\Customer;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Invoice;
use App\Invoice\PaymentTerms;
use App\Team\Team;
use Carbon\CarbonImmutable;

/**
 * Drafts one invoice for a customer from the billing lines given, issued on
 * the day given, in their currency and due by their payment terms.
 */
final readonly class DraftCustomerInvoice
{
    public const int TAX_RATE_BASIS_POINTS = 1000;

    /**
     * @param  list<BillingLine>  $lines
     */
    public function __construct(
        private Customer $customer,
        private Team $team,
        private array $lines,
        private CarbonImmutable $day,
    ) {}

    public function __invoke(): Invoice
    {
        return CreateInvoice::fromInvoice(
            Invoice::new()
                ->setTeamId($this->customer->getTeamId()->toString())
                ->setCustomerId($this->customer->getId()->toString())
                ->setCurrency($this->customer->getCurrency()->toEnum()->value)
                ->setIssuedOn($this->day)
                ->setDueOn(PaymentTerms::dueOn($this->day, $this->customer, $this->team))
                ->setTaxRateBasisPoints(self::TAX_RATE_BASIS_POINTS),
            array_map(fn (BillingLine $line): array => $line->toInvoiceItem(), $this->lines),
        );
    }

    /**
     * @param  list<BillingLine>  $lines
     */
    public static function from(Customer $customer, Team $team, array $lines, CarbonImmutable $day): Invoice
    {
        return new self($customer, $team, $lines, $day)->handle();
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
