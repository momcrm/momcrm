<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

/**
 * Who may subscribe to which channel, through the endpoint Echo asks. Tests
 * otherwise broadcast nowhere, so this signs subscriptions as Reverb would,
 * with the channels in routes/channels.php registered on it.
 */
beforeEach(function (): void {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
    ]);

    require base_path('routes/channels.php');
});

function subscribe(string $channel): TestResponse
{
    return postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $channel]);
}

it("lets a portal contact listen to their customer's tickets, and no one else's", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    $otherCustomers = portalTicket(portalContact(['email' => 'pat@elsewhere.test']));
    actingAs($contact, 'portal');

    subscribe("private-portal.tickets.{$ticket->id}")->assertOk()->assertJsonStructure(['auth']);
    subscribe("private-portal.customers.{$contact->customer_id}.tickets")->assertOk();
    subscribe("private-portal.tickets.{$otherCustomers->id}")->assertForbidden();
    subscribe("private-portal.customers.{$otherCustomers->customer_id}.tickets")->assertForbidden();
});

it('keeps a portal contact off staff channels, even for their own ticket', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    actingAs($contact, 'portal');

    subscribe("private-tickets.{$ticket->id}")->assertForbidden();
    subscribe("private-teams.{$contact->team_id}.tickets")->assertForbidden();
});

it("keeps staff off the portal's channels", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    $owner = UserEloquentModel::query()->where('current_team_id', $contact->team_id)->sole();
    actingAs($owner);

    subscribe("private-teams.{$contact->team_id}.tickets")->assertOk();
    subscribe("private-portal.tickets.{$ticket->id}")->assertForbidden();
});
