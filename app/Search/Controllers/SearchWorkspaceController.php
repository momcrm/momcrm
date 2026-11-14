<?php

declare(strict_types=1);

namespace App\Search\Controllers;

use App\Contact\Actions\FindContact;
use App\Contact\ContactEloquentModel;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerEloquentModel;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceEloquentModel;
use App\Ticket\Actions\FindTicket;
use App\Ticket\TicketEloquentModel;
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
                'subtitle' => $ticket->customer?->name,
                'url' => route('tickets.show', $ticket->id),
            ])
            ->values()
            ->all();
    }
}
