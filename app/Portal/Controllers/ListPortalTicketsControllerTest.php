<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Ticket\TicketEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's open tickets, not other customers' or closed ones", function (): void {
    $contact = portalContact();
    $open = portalTicket($contact, ['title' => 'Printer jam']);
    portalTicket($contact, ['title' => 'Old one'])->forceFill(['status_id' => ticketStatusId($contact->team_id, 'Closed')])->save();
    portalTicket($contact, ['title' => 'Someone else', 'customer_id' => portalContact()->customer_id]);

    actingAs($contact, 'portal')
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/tickets/index')
            ->has('tickets', 1)
            ->where('tickets.0.id', $open->id)
            ->where('tickets.0.status.is_closed', false)
            ->missing('tickets.0.assignee_id')
            ->where('canCreate', true));
});

it('lists closed tickets on request', function (): void {
    $contact = portalContact();
    portalTicket($contact);
    $closed = TicketEloquentModel::factory()->closed()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id]);

    actingAs($contact, 'portal')
        ->get(route('tickets.index', ['status' => 'closed']))
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 1)->where('tickets.0.id', $closed->id));
});

it('shows only the tickets they raised when a rule says so', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();
    AccessRuleEloquentModel::factory()->forContacts()->create([
        'team_id' => $contact->team_id,
        'resources' => ['ticket'],
        'actions' => ['view'],
        'conditions' => [['attribute' => 'ticket.subject_is_requester', 'operator' => 'is_true']],
    ]);
    $theirs = portalTicket($contact, ['contact_id' => $contact->id]);
    portalTicket($contact);

    actingAs($contact, 'portal')
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 1)->where('tickets.0.id', $theirs->id)->where('canCreate', false));
});

it('is forbidden when no rule lets the contact see tickets', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->get(route('tickets.index'))->assertForbidden();
});

it("reloads the list when any of the customer's tickets change", function (): void {
    $contact = portalContact();

    $response = actingAs($contact, 'portal')->get(route('tickets.index'))->assertOk();

    expect(liveListeners($response, 'tickets'))->toBe(["private-portal.customers.{$contact->customer_id}.tickets ticket.changed"]);
});
