<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Charge\Actions\FindCharge;
use App\Charge\Charge;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Product\Actions\FindProduct;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's invoices tab, optionally with one status.
 */
final class ListCustomerInvoicesController
{
    private const int BILLED_MONTHS = 3;

    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $status = InvoiceStatusEnum::tryFrom($request->string('status')->toString());

        return Inertia::render('customers/workspace/invoices', [
            ...$workspace->props($request),
            'invoices' => FindInvoice::paginateForTeam($workspace->teamId, $status, customerId: $workspace->customerId())
                ->through(fn (Invoice $invoice): array => FindInvoice::listItem($invoice)),
            'filters' => ['status' => $status?->value],
            'statuses' => InvoiceStatusEnum::options(),
            'charges' => self::charges($workspace->customerId(), $workspace->teamId),
            'products' => FindProduct::listForTeam($workspace->teamId)->toSummaries(),
            'canCharge' => Gate::allows('create', Charge::class),
        ]);
    }

    /**
     * The customer's charges, waiting ones first, each with the invoice it
     * is on once billed. Billed ones go back a few months.
     *
     * @return list<array<string, mixed>>
     */
    private static function charges(string $customerId, string $teamId): array
    {
        $charges = FindCharge::forCustomer($customerId, $teamId);
        $billed = FindCharge::billedInvoices(array_values($charges->map(fn (Charge $charge): string => $charge->getId()->toString())->all()));
        $since = CarbonImmutable::today()->subMonths(self::BILLED_MONTHS);
        $summaries = [];

        foreach ($charges as $charge) {
            $billedOn = $billed[$charge->getId()->toString()] ?? null;

            if ($billedOn === null || $charge->getChargedOn()->toCarbon()->gte($since)) {
                $summaries[] = $charge->toSummary($billedOn);
            }
        }

        usort($summaries, fn (array $a, array $b): int => ($a['invoice'] === null ? 0 : 1) <=> ($b['invoice'] === null ? 0 : 1));

        return $summaries;
    }
}
