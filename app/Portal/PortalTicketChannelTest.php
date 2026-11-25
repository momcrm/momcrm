<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Portal\PortalTicketChannel;

it("lets a contact listen to their customer's ticket", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);

    expect(new PortalTicketChannel()->join($contact, $ticket->id))->toBeTrue();
});

it("keeps a contact off another customer's ticket", function (): void {
    $contact = portalContact();
    $theirs = portalTicket(portalContact(['email' => 'pat@elsewhere.test']));

    expect(new PortalTicketChannel()->join($contact, $theirs->id))->toBeFalse();
});

it('keeps a contact off a ticket a rule hides from them', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();
    AccessRuleEloquentModel::factory()->forContacts()->create([
        'team_id' => $contact->team_id,
        'resources' => ['ticket'],
        'actions' => ['view'],
        'conditions' => [['attribute' => 'ticket.subject_is_requester', 'operator' => 'is_true']],
    ]);
    $colleagues = portalTicket($contact);

    expect(new PortalTicketChannel()->join($contact, $colleagues->id))->toBeFalse();
});
