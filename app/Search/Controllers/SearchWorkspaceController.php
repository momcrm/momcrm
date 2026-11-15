<?php

declare(strict_types=1);

namespace App\Search\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Project\Actions\FindProject;
use App\Project\Project;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Ticket;
use App\User\UserEloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Powers the ⌘K palette: the current team's records matching a search term,
 * or the most recent tickets when there is no term yet.
 */
final class SearchWorkspaceController
{
    private const int RESULTS_PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json([
                'recentTickets' => $this->tickets($teamId, null),
            ]);
        }

        return response()->json([
            'tickets' => $this->tickets($teamId, $term),
            'projects' => FindProject::listForTeam($teamId, $user->id, $term)
                ->take(self::RESULTS_PER_GROUP)
                ->map(fn (Project $project): array => [
                    'id' => $project->getId()->toString(),
                    'title' => $project->getName()->toString(),
                    'subtitle' => $project->getCustomer()->toNullableData()?->getName()->toString(),
                    'url' => route('projects.show', $project->getId()->toString()),
                ])
                ->values()
                ->all(),
            'customers' => FindCustomer::paginateForTeam($teamId, $term, self::RESULTS_PER_GROUP)
                ->getCollection()
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->getId()->toString(),
                    'title' => $customer->getName()->toString(),
                    'subtitle' => $customer->getEmail()->toNullableString(),
                    'url' => route('customers.show', $customer->getId()->toString()),
                ])
                ->all(),
            'contacts' => FindContact::paginateForTeam($teamId, $term, self::RESULTS_PER_GROUP)
                ->getCollection()
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->getId()->toString(),
                    'title' => $contact->getFullName(),
                    'subtitle' => $contact->getCustomer()->toNullableData()?->getName()->toString(),
                    'url' => route('contacts.edit', $contact->getId()->toString()),
                ])
                ->all(),
            'invoices' => preg_match('/\d/', $term) === 1
                ? FindInvoice::paginateForTeam($teamId, search: $term, perPage: self::RESULTS_PER_GROUP)
                    ->getCollection()
                    ->map(fn (Invoice $invoice): array => [
                        'id' => $invoice->getId()->toString(),
                        'title' => "Invoice #{$invoice->getNumber()->toInteger()}",
                        'subtitle' => $invoice->getCustomer()->toNullableData()?->getName()->toString(),
                        'url' => route('invoices.show', $invoice->getId()->toString()),
                    ])
                    ->all()
                : [],
        ]);
    }

    /**
     * @return array<int, array{id: string, number: int, title: string, status: string, subtitle: ?string, url: string}>
     */
    private function tickets(string $teamId, ?string $term): array
    {
        return FindTicket::paginateForTeam($teamId, FindTicket::FILTER_ALL, $term, perPage: self::RESULTS_PER_GROUP)
            ->getCollection()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->getId()->toString(),
                'number' => $ticket->getNumber()->toInteger(),
                'title' => $ticket->getTitle()->toString(),
                'status' => $ticket->getStatus()->toString(),
                'subtitle' => $ticket->getCustomer()->toNullableData()?->getName()->toString() ?? $ticket->getProject()->toNullableData()?->getName()->toString(),
                'url' => route('tickets.show', $ticket->getId()->toString()),
            ])
            ->values()
            ->all();
    }
}
