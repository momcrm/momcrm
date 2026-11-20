<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;

use function Pest\Laravel\actingAs;

it("closes the customer's ticket", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);

    actingAs($contact, 'portal')->post(route('portal.tickets.close', $ticket->id))->assertRedirect();

    expect($ticket->fresh())
        ->status_id->toBe(ticketStatusId($contact->team_id, 'Closed'))
        ->closed_at->not->toBeNull();
});

it('is forbidden when contacts may not close tickets', function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $contact->team_id, 'resources' => ['ticket'], 'actions' => ['view']]);

    actingAs($contact, 'portal')->post(route('portal.tickets.close', $ticket->id))->assertForbidden();
});
