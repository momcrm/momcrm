<?php

declare(strict_types=1);

namespace App\Customer;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Number;

/**
 * A customer's account over a period, as Amphora's statement shows it: what
 * they owed going in, each invoice (a debit, on the day it was issued) and
 * each payment (a credit, on the day it was paid) with a running balance,
 * what they owe at the end, and how old today's unpaid invoices are. The
 * statement tab, its PDF and its email all show this.
 */
final readonly class CustomerStatement
{
    /**
     * The ageing buckets, by days past due.
     */
    public const array AGES = [
        'current' => 'Current',
        'days_1_30' => '1–30 days',
        'days_31_60' => '31–60 days',
        'days_61_90' => '61–90 days',
        'days_over_90' => 'Over 90 days',
    ];

    /**
     * @param  list<array{date: string, type: string, id: string, reference: string, description: string, debit_in_cents: int, credit_in_cents: int, balance_in_cents: int}>  $entries
     * @param  array<string, int>  $ages
     */
    public function __construct(
        public Customer $customer,
        public Team $team,
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public int $openingBalanceInCents,
        public array $entries,
        public int $closingBalanceInCents,
        public array $ages,
        public int $creditInCents,
    ) {}

    public static function for(Customer $customer, CarbonImmutable $from, CarbonImmutable $to, ?CarbonImmutable $today = null): self
    {
        $today ??= CarbonImmutable::today();
        $teamId = $customer->getTeamId()->toString();
        $customerId = $customer->getId()->toString();
        $invoices = FindInvoice::billedToCustomer($customerId, $teamId);
        $payments = FindPayment::forCustomer($customerId, $teamId);
        $fromDay = $from->toDateString();
        $toDay = $to->toDateString();

        $opening = 0;
        $movements = [];

        foreach ($invoices as $invoice) {
            $day = $invoice->getIssuedOn()->toCarbon()->toDateString();
            $total = $invoice->totals()->totalInCents;

            if ($day < $fromDay) {
                $opening += $total;
            } elseif ($day <= $toDay) {
                $movements[] = self::invoiceEntry($invoice, $day, $total);
            }
        }

        foreach ($payments as $payment) {
            $day = $payment->getPaidOn()->toCarbon()->toDateString();
            $amount = $payment->getAmountInCents()->toInteger();

            if ($day < $fromDay) {
                $opening -= $amount;
            } elseif ($day <= $toDay) {
                $movements[] = self::paymentEntry($payment, $day, $amount);
            }
        }

        // By day; on the same day invoices come before payments.
        usort($movements, fn (array $a, array $b): int => [$a['date'], $a['order'], $a['reference']] <=> [$b['date'], $b['order'], $b['reference']]);

        $balance = $opening;
        $entries = [];

        foreach ($movements as $movement) {
            $balance += $movement['debit_in_cents'] - $movement['credit_in_cents'];
            unset($movement['order']);
            $entries[] = [...$movement, 'balance_in_cents' => $balance];
        }

        return new self(
            $customer,
            FindTeam::byId($teamId),
            $from,
            $to,
            $opening,
            $entries,
            $balance,
            self::ages($invoices->all(), $today),
            FindPayment::creditForCustomers($teamId, [$customerId])[$customerId] ?? 0,
        );
    }

    /**
     * The period asked for in ?from= and ?to= (YYYY-MM-DD), or by default
     * the last three months: from the start of the month two months ago to
     * today. A period that ends before it starts is turned around.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function periodFrom(Request $request, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $date = function (string $key) use ($request): ?CarbonImmutable {
            $value = $request->string($key)->toString();

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null : null;
        };

        $from = $date('from') ?? $today->subMonthsNoOverflow(2)->startOfMonth();
        $to = $date('to') ?? $today;

        return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
    }

    /**
     * The file name the PDF downloads as, e.g. Statement Acme IT 2026-11-30.pdf.
     */
    public function fileName(): string
    {
        return 'Statement '.str_replace(['/', '\\'], '-', $this->customer->getName()->toString()).' '.$this->to->toDateString().'.pdf';
    }

    /**
     * An amount in the customer's currency, e.g. A$1,250.00.
     */
    public function money(int $cents): string
    {
        return (string) Number::currency($cents / 100, $this->customer->getCurrency()->toEnum()->value);
    }

    /**
     * @return array{customer: array{id: string, name: string}, currency: string, from: string, to: string, opening_balance_in_cents: int, entries: list<array{date: string, type: string, id: string, reference: string, description: string, debit_in_cents: int, credit_in_cents: int, balance_in_cents: int}>, closing_balance_in_cents: int, ages: list<array{key: string, label: string, amount_in_cents: int}>, credit_in_cents: int}
     */
    public function toArray(): array
    {
        return [
            'customer' => ['id' => $this->customer->getId()->toString(), 'name' => $this->customer->getName()->toString()],
            'currency' => $this->customer->getCurrency()->toEnum()->value,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'opening_balance_in_cents' => $this->openingBalanceInCents,
            'entries' => $this->entries,
            'closing_balance_in_cents' => $this->closingBalanceInCents,
            'ages' => array_map(
                fn (string $key, string $label): array => ['key' => $key, 'label' => $label, 'amount_in_cents' => $this->ages[$key] ?? 0],
                array_keys(self::AGES),
                self::AGES,
            ),
            'credit_in_cents' => $this->creditInCents,
        ];
    }

    /**
     * @return array{date: string, type: string, id: string, reference: string, description: string, debit_in_cents: int, credit_in_cents: int, order: int}
     */
    private static function invoiceEntry(Invoice $invoice, string $day, int $total): array
    {
        $due = $invoice->getDueOn()->toNullableCarbon();

        return [
            'date' => $day,
            'type' => 'invoice',
            'id' => $invoice->getId()->toString(),
            'reference' => $invoice->getReference(),
            'description' => $due === null
                ? __('Invoice :reference', ['reference' => $invoice->getReference()])
                : __('Invoice :reference · due :due', ['reference' => $invoice->getReference(), 'due' => $due->format('j M Y')]),
            'debit_in_cents' => $total,
            'credit_in_cents' => 0,
            'order' => 0,
        ];
    }

    /**
     * @return array{date: string, type: string, id: string, reference: string, description: string, debit_in_cents: int, credit_in_cents: int, order: int}
     */
    private static function paymentEntry(Payment $payment, string $day, int $amount): array
    {
        return [
            'date' => $day,
            'type' => 'payment',
            'id' => $payment->getId()->toString(),
            'reference' => $payment->getReferenceNumber(),
            'description' => __('Payment :reference · :method', ['reference' => $payment->getReferenceNumber(), 'method' => $payment->getMethod()->toEnum()->label()]),
            'debit_in_cents' => 0,
            'credit_in_cents' => $amount,
            'order' => 1,
        ];
    }

    /**
     * What is owing today on sent invoices, by how long past due.
     *
     * @param  array<int, Invoice>  $invoices
     * @return array<string, int>
     */
    private static function ages(array $invoices, CarbonImmutable $today): array
    {
        $ages = array_fill_keys(array_keys(self::AGES), 0);

        foreach ($invoices as $invoice) {
            $owing = $invoice->balanceAfter($invoice->totals());

            if (! $invoice->canTakePayments() || $owing === 0) {
                continue;
            }

            $due = $invoice->getDueOn()->toNullableCarbon()?->startOfDay();
            $daysLate = $due === null || $due->greaterThanOrEqualTo($today) ? 0 : (int) $due->diffInDays($today);

            $bucket = match (true) {
                $daysLate === 0 => 'current',
                $daysLate <= 30 => 'days_1_30',
                $daysLate <= 60 => 'days_31_60',
                $daysLate <= 90 => 'days_61_90',
                default => 'days_over_90',
            };

            $ages[$bucket] += $owing;
        }

        return $ages;
    }
}
