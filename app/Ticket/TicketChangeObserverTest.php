<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Comment\CommentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\Events\TicketChanged;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Event;

use function Illuminate\Support\defer;

/**
 * Saves a ticket and lets its own broadcast go out, so a test sees only what
 * happens next.
 */
function ticketWithNothingPending(): TicketEloquentModel
{
    $ticket = TicketEloquentModel::factory()->create();
    defer()->invoke();

    return $ticket;
}

function assertTicketChangedOnce(TicketEloquentModel $ticket): void
{
    Event::assertDispatchedTimes(TicketChanged::class, 1);
    Event::assertDispatched(TicketChanged::class, fn (TicketChanged $event): bool => $event->ticketId === $ticket->id && $event->teamId === $ticket->team_id);
}

it('tells open pages when a ticket is updated', function (): void {
    $ticket = ticketWithNothingPending();
    Event::fake([TicketChanged::class]);

    $ticket->update(['title' => 'Printer on fire']);
    defer()->invoke();

    assertTicketChangedOnce($ticket);
});

it('tells open pages when a comment is added to a ticket', function (): void {
    $ticket = ticketWithNothingPending();
    Event::fake([TicketChanged::class]);

    CommentEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'team_id' => $ticket->team_id]);
    defer()->invoke();

    assertTicketChangedOnce($ticket);
});

it("tells open pages when an email on a ticket changes, such as a reply's delivery", function (): void {
    $ticket = ticketWithNothingPending();
    Event::fake([TicketChanged::class]);

    MailboxMessageEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'team_id' => $ticket->team_id]);
    defer()->invoke();

    assertTicketChangedOnce($ticket);
});

it('tells open pages when a file is attached to a ticket', function (): void {
    $ticket = ticketWithNothingPending();
    Event::fake([TicketChanged::class]);

    AttachmentEloquentModel::factory()->on($ticket)->create();
    defer()->invoke();

    assertTicketChangedOnce($ticket);
});

it('sends one broadcast per ticket however many changes the request made', function (): void {
    $ticket = ticketWithNothingPending();
    Event::fake([TicketChanged::class]);

    $ticket->update(['title' => 'Printer on fire']);
    $ticket->update(['title' => 'Printer still on fire']);
    CommentEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'team_id' => $ticket->team_id]);
    defer()->invoke();

    Event::assertDispatchedTimes(TicketChanged::class, 1);
});

it('tells the parent a subtask left and the one it joined', function (): void {
    $from = TicketEloquentModel::factory()->create();
    $to = TicketEloquentModel::factory()->create(['team_id' => $from->team_id]);
    $subtask = TicketEloquentModel::factory()->create(['team_id' => $from->team_id, 'parent_id' => $from->id]);
    defer()->invoke();
    Event::fake([TicketChanged::class]);

    $subtask->update(['parent_id' => $to->id]);
    defer()->invoke();

    $ticketIds = Event::dispatched(TicketChanged::class)->map(fn (array $dispatch): string => $dispatch[0]->ticketId)->sort()->values()->all();
    expect($ticketIds)->toBe(collect([$subtask->id, $from->id, $to->id])->sort()->values()->all());
});

it("doesn't tell any ticket about a file on something else", function (): void {
    $customer = CustomerEloquentModel::factory()->create();
    Event::fake([TicketChanged::class]);

    AttachmentEloquentModel::factory()->on($customer)->create();
    defer()->invoke();

    Event::assertNotDispatched(TicketChanged::class);
});

it("tells the customer's portal about a change to their ticket", function (): void {
    $ticket = portalTicket(portalContact());
    defer()->invoke();
    Event::fake([TicketChanged::class]);

    CommentEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'team_id' => $ticket->team_id]);
    defer()->invoke();

    Event::assertDispatched(TicketChanged::class, fn (TicketChanged $event): bool => $event->customerId === $ticket->customer_id);
});

it('tells the portal of the customer a ticket moved away from, and of the one it moved to', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    $to = CustomerEloquentModel::factory()->create(['team_id' => $contact->team_id]);
    defer()->invoke();
    Event::fake([TicketChanged::class]);

    $ticket->update(['customer_id' => $to->id]);
    defer()->invoke();

    $customerIds = Event::dispatched(TicketChanged::class)->map(fn (array $dispatch): ?string => $dispatch[0]->customerId)->sort()->values()->all();
    expect($customerIds)->toBe(collect([$contact->customer_id, $to->id])->sort()->values()->all());
});

it('tells the customer\'s portal when their ticket is deleted', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    defer()->invoke();
    Event::fake([TicketChanged::class]);

    $ticket->delete();
    defer()->invoke();

    Event::assertDispatched(TicketChanged::class, fn (TicketChanged $event): bool => $event->customerId === $contact->customer_id);
});
