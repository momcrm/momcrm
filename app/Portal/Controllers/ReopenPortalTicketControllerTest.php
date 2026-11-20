<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;

use function Pest\Laravel\actingAs;

it("reopens the customer's closed ticket", function (): void {
    $contact = portalContact();
    $ticket = TicketEloquentModel::factory()->closed()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id]);

    actingAs($contact, 'portal')->post(route('portal.tickets.reopen', $ticket->id))->assertRedirect();

    expect($ticket->fresh())
        ->status_id->toBe(ticketStatusId($contact->team_id, 'New'))
        ->closed_at->toBeNull();
});
