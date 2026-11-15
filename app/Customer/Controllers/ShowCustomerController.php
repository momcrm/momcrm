<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Asset\Actions\FindAsset;
use App\Asset\Asset;
use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceEloquentModel;
use App\Location\Actions\FindLocation;
use App\Location\Location;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\ProjectEloquentModel;
use App\Service\Actions\FindService;
use App\Service\Service;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\TicketEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowCustomerController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindCustomer::byIdForTeam($customer, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        $customerId = $found->getId()->toString();

        return Inertia::render('customers/show', [
            'customer' => $found->toSummary(),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Customer, $found),
            'contacts' => FindContact::forCustomer($customerId, $teamId)
                ->map(fn (Contact $contact): array => $contact->toSummary())
                ->values()
                ->all(),
            'locations' => FindLocation::forCustomer($customerId, $teamId)
                ->map(fn (Location $location): array => $location->toSummary())
                ->values()
                ->all(),
            'assets' => FindAsset::forCustomer($customerId, $teamId)
                ->map(fn (Asset $asset): array => $asset->toSummary())
                ->values()
                ->all(),
            'services' => FindService::forCustomer($customerId, $teamId)
                ->map(fn (Service $service): array => $service->toSummary())
                ->values()
                ->all(),
            'projects' => FindProject::forCustomer($teamId, (string) $request->user()?->id, $customerId)
                ->map(fn (ProjectEloquentModel $model): array => ListProjectsController::listItem($model, []))
                ->values()
                ->all(),
            'tickets' => FindTicket::paginateForTeam($teamId, FindTicket::FILTER_ALL, customerId: $customerId, perPage: 10)
                ->getCollection()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->all(),
            'invoices' => FindInvoice::paginateForTeam($teamId, customerId: $customerId, perPage: 10)
                ->getCollection()
                ->map(fn (InvoiceEloquentModel $model): array => FindInvoice::listItem($model))
                ->all(),
        ]);
    }
}
