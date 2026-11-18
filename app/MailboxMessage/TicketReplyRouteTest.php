<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\TicketReplyRoute;
use App\Team\TeamEloquentModel;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;

it('replies to whoever wrote in, before the ticket\'s contact', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $contact = ContactEloquentModel::factory()->create(['team_id' => $team->id, 'email' => 'Kim@Acme.test']);
    $ticket = ['team_id' => $team->id, 'customer_id' => $contact->customer_id, 'contact_id' => $contact->id];

    $emailed = TicketReplyRoute::forTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create([...$ticket, 'requester_email' => 'sam@acme.test'])));
    $typed = TicketReplyRoute::forTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create($ticket)));

    expect($emailed->to)->toBe('sam@acme.test')
        ->and($typed->to)->toBe('kim@acme.test');
});

it('has nobody to reply to on a ticket with no requester and no contact address', function (): void {
    $team = TeamEloquentModel::factory()->create();
    MailboxEloquentModel::factory()->create(['team_id' => $team->id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $team->id, 'email' => null]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $contact->customer_id, 'contact_id' => $contact->id]);

    $route = TicketReplyRoute::forTicket(Ticket::fromEloquentModel($ticket));

    expect($route->to)->toBeNull()
        ->and($route->isOpen())->toBeFalse();
});

it('sends from the mailbox the ticket came in through, or else the team\'s first connected one', function (): void {
    $team = TeamEloquentModel::factory()->create();
    MailboxEloquentModel::factory()->needsReconnect()->create(['team_id' => $team->id, 'address' => 'a-broken@helpdesk.test']);
    MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'address' => 'billing@helpdesk.test']);
    $support = MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'address' => 'support@helpdesk.test']);
    MailboxEloquentModel::factory()->create(['address' => 'another-team@elsewhere.test']);
    $ticket = ['team_id' => $team->id, 'requester_email' => 'sam@acme.test'];

    $viaSupport = TicketReplyRoute::forTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create([...$ticket, 'mailbox_id' => $support->id])));
    $typed = TicketReplyRoute::forTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create($ticket)));

    expect($viaSupport->toArray())->toBe(['to' => 'sam@acme.test', 'from' => 'support@helpdesk.test'])
        ->and($viaSupport->isOpen())->toBeTrue()
        ->and($typed->toArray())->toBe(['to' => 'sam@acme.test', 'from' => 'billing@helpdesk.test']);
});

it('cannot send when the team has no connected mailbox', function (): void {
    $team = TeamEloquentModel::factory()->create();
    MailboxEloquentModel::factory()->needsReconnect()->create(['team_id' => $team->id]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $team->id, 'requester_email' => 'sam@acme.test']);

    $route = TicketReplyRoute::forTicket(Ticket::fromEloquentModel($ticket));

    expect($route->toArray())->toBe(['to' => 'sam@acme.test', 'from' => null])
        ->and($route->isOpen())->toBeFalse();
});
