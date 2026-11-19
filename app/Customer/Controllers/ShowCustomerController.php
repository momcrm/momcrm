<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Contract\Actions\FindContract;
use App\Contract\Contract;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Service\Actions\FindService;
use App\Service\Service;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's dashboard: what they owe, the tickets and draft invoices
 * waiting on someone, and services and contracts that end soon.
 */
final class ShowCustomerController
{
    public const int EXPIRING_WITHIN_DAYS = 30;

    public const int CARD_ROWS = 5;

    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $customerId = $workspace->customerId();
        $today = CarbonImmutable::today();

        return Inertia::render('customers/workspace/dashboard', [
            ...$workspace->props($request),
            'linkedRecords' => FindLinkedRecords::to((string) $request->user()?->current_team_id, (string) $request->user()?->id, CustomFieldRelationEnum::Customer, $customerId),
            'money' => FindInvoice::balancesForCustomers($workspace->teamId, [$customerId])[$customerId]
                ?? ['balance_in_cents' => 0, 'current_in_cents' => 0, 'overdue_in_cents' => 0],
            'openTickets' => FindTicket::paginateForTeam($workspace->teamId, FindTicket::FILTER_OPEN, customerId: $customerId, perPage: self::CARD_ROWS)
                ->getCollection()
                ->map(fn (Ticket $ticket): array => ListTicketsController::listItem($ticket))
                ->all(),
            'draftInvoices' => FindInvoice::paginateForTeam($workspace->teamId, InvoiceStatusEnum::Draft, customerId: $customerId, perPage: self::CARD_ROWS)
                ->getCollection()
                ->map(fn (Invoice $invoice): array => FindInvoice::listItem($invoice))
                ->all(),
            'expiringServices' => FindService::forCustomer($customerId, $workspace->teamId)
                ->filter(fn (Service $service): bool => $service->isActive()
                    && ! $service->isArchived()
                    && $service->getEndsOn()->isNotNull()
                    && $service->getEndsOn()->toCarbon()->betweenIncluded($today, $today->addDays(self::EXPIRING_WITHIN_DAYS)))
                ->sortBy(fn (Service $service): string => $service->getEndsOn()->toCarbon()->toDateString())
                ->take(self::CARD_ROWS)
                ->values()
                ->toSummaries(),
            'expiringContracts' => FindContract::endingSoonForCustomer($customerId, $workspace->teamId, self::EXPIRING_WITHIN_DAYS)
                ->take(self::CARD_ROWS)
                ->map(fn (Contract $contract): array => $contract->toSummary())
                ->values()
                ->all(),
        ]);
    }
}
