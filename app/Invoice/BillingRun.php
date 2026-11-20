<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Billing\CustomerBillingLines;
use App\Service\Actions\RunServiceInvoices;
use App\Team\Team;
use Carbon\CarbonImmutable;

/**
 * What a billing run would draft for a team on a day: each customer the
 * monthly run bills, with the lines they are due, by customer name. The run
 * page shows it, and drafts exactly the lines picked from it.
 */
final readonly class BillingRun
{
    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, list<BillingLine>>  $lines
     */
    private function __construct(
        public CarbonImmutable $day,
        public array $customers,
        public array $lines,
    ) {}

    public static function for(Team $team, CarbonImmutable $day): self
    {
        $lines = CustomerBillingLines::forCustomers(new RunServiceInvoices($day, $team->getId()->toString())->billableCustomerIds(), $day);
        $customers = FindCustomer::byIdsForTeams(array_map(strval(...), array_keys($lines)));

        uasort($customers, fn (Customer $a, Customer $b): int => strcasecmp($a->getName()->toString(), $b->getName()->toString()));

        return new self($day, $customers, $lines);
    }

    /**
     * @return list<array{id: string, name: string, currency: string, due_on: string, total_in_cents: int, lines: list<array{key: string, kind: string, description: string, quantity: int, unit_price_in_cents: int, total_in_cents: int}>}>
     */
    public function toArray(Team $team): array
    {
        $rows = [];

        foreach ($this->customers as $id => $customer) {
            $lines = $this->lines[$id] ?? [];

            $rows[] = [
                'id' => $id,
                'name' => $customer->getName()->toString(),
                'currency' => $customer->getCurrency()->toEnum()->value,
                'due_on' => PaymentTerms::dueOn($this->day, $customer, $team)->toDateString(),
                'total_in_cents' => array_sum(array_map(fn (BillingLine $line): int => $line->totalInCents(), $lines)),
                'lines' => array_map(fn (BillingLine $line): array => $line->toArray(), $lines),
            ];
        }

        return $rows;
    }
}
