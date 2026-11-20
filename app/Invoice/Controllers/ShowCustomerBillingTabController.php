<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Charge\Actions\FindCharge;
use App\Charge\Charge;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Billing\BillingMonth;
use App\Invoice\CustomerBillingPageEnum;
use App\Invoice\Invoice;
use App\InvoiceItem\Actions\FindInvoiceItem;
use App\Product\Actions\FindProduct;
use App\Rate\Actions\FindRate;
use App\Service\Actions\FindService;
use App\Service\Service;
use App\Service\ServiceStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A customer's Billing tab, as Amphora's company Billing section: Multiple
 * Service Billing, Single Service Billing, Product Billing and Combine
 * Invoice Items, each a few steps. Sent the customer's billable services,
 * the products to bill from, and the lines waiting to be invoiced.
 */
final class ShowCustomerBillingTabController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer, ?string $page = null): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $currency = $workspace->customer->getCurrency()->toEnum();
        $services = FindService::forCustomer($workspace->customerId(), $workspace->teamId)
            ->filter(fn (Service $service): bool => $service->getStatus()->toEnum() === ServiceStatusEnum::Active
                && ! $service->isArchived()
                && $service->getRateId()->isNotNull());
        $serviceIds = array_values($services->map(fn (Service $service): string => $service->getId()->toString())->all());
        $rates = FindRate::byIds(array_values(array_unique($services->map(fn (Service $service): string => $service->getRateId()->toString())->all())));
        $billedThrough = FindInvoiceItem::billedThroughForServices($serviceIds);

        return Inertia::render('customers/workspace/billing', [
            ...$workspace->props($request),
            'page' => (CustomerBillingPageEnum::tryFrom((string) $page) ?? CustomerBillingPageEnum::Multiple)->value,
            'pages' => CustomerBillingPageEnum::options(),
            'month' => BillingMonth::fromString(null)->toString(),
            'services' => array_values($services->map(fn (Service $service): array => $service->toSummary(
                $rates[$service->getRateId()->toString()] ?? null,
                $currency,
                $billedThrough[$service->getId()->toString()] ?? null,
            ))->all()),
            'products' => FindProduct::listForTeam($workspace->teamId)->toSummaries(),
            'waiting' => array_map(
                fn (Charge $charge): array => $charge->toSummary(),
                FindCharge::unbilledForCustomers([$workspace->customerId()], $currency)[$workspace->customerId()] ?? [],
            ),
            'can' => [
                'invoice' => Gate::allows('create', Invoice::class),
                'charge' => Gate::allows('create', Charge::class),
            ],
        ]);
    }
}
