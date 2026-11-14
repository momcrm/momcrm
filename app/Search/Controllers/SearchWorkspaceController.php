<?php

declare(strict_types=1);

namespace App\Search\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\ContactEloquentModel;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerEloquentModel;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceEloquentModel;
use App\Project\Actions\FindProject;
use App\Project\ProjectEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
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
                ->map(fn (ProjectEloquentModel $project): array => [
                    'id' => $project->id,
                    'title' => $project->name,
                    'subtitle' => $project->customer?->name,
                    'url' => route('projects.show', $project->id),
                ])
                ->values()
                ->all(),
            'customers' => FindCustomer::paginateForTeam($teamId, $term, self::RESULTS_PER_GROUP)
                ->getCollection()
                ->map(fn (CustomerEloquentModel $customer): array => [
                    'id' => $customer->id,
                    'title' => $customer->name,
                    'subtitle' => $customer->email,
                    'url' => route('customers.show', $customer->id),
                ])
                ->all(),
            'contacts' => FindContact::paginateForTeam($teamId, $term, self::RESULTS_PER_GROUP)
                ->getCollection()
                ->map(fn (ContactEloquentModel $contact): array => [
                    'id' => $contact->id,
                    'title' => trim("{$contact->first_name} {$contact->last_name}"),
                    'subtitle' => $contact->customer?->name,
                    'url' => route('contacts.edit', $contact->id),
                ])
                ->all(),
            'invoices' => preg_match('/\d/', $term) === 1
                ? FindInvoice::paginateForTeam($teamId, search: $term, perPage: self::RESULTS_PER_GROUP)
                    ->getCollection()
                    ->map(fn (InvoiceEloquentModel $invoice): array => [
                        'id' => $invoice->id,
                        'title' => "Invoice #{$invoice->number}",
                        'subtitle' => $invoice->customer?->name,
                        'url' => route('invoices.show', $invoice->id),
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
            ->map(fn (TicketEloquentModel $ticket): array => [
                'id' => $ticket->id,
                'number' => $ticket->number,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'subtitle' => self::ticketSubtitle($ticket),
                'url' => route('tickets.show', $ticket->id),
            ])
            ->values()
            ->all();
    }

    /**
     * Who the ticket is for: its customer, or its project for internal work.
     */
    private static function ticketSubtitle(TicketEloquentModel $ticket): ?string
    {
        $customer = $ticket->getRelationValue(TicketRelationship::Customer->value);
        $project = $ticket->getRelationValue(TicketRelationship::Project->value);

        return match (true) {
            $customer instanceof CustomerEloquentModel => $customer->name,
            $project instanceof ProjectEloquentModel => $project->name,
            default => null,
        };
    }
}
