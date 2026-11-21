<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Portal\PortalSession;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;

it('lets an admin view the portal as a contact, read-only and on record', function (): void {
    $contact = portalContact();
    $admin = UserEloquentModel::factory()->inTeam($contact->team_id, TeamRoleEnum::Admin)->create();
    $ticket = portalTicket($contact);

    actingAs($admin)
        ->post(route('contacts.view-in-portal', $contact->id))
        ->assertRedirectToRoute('home')
        ->assertSessionHas(PortalSession::VIEWING_AS, $admin->id);

    assertAuthenticatedAs($contact, 'portal');
    expect(AuditLogEloquentModel::query()->withoutGlobalScopes()->where('auditable_id', $contact->id)->where('event', 'viewed')->sole()->user_id)->toBe($admin->id);

    auth()->shouldUse('portal');

    $this->get(route('tickets.show', $ticket->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('portal.viewingAs', true)
            ->where('can', ['reply' => false, 'attach' => false, 'close' => false, 'reopen' => false]));

    $this->post(route('portal.tickets.replies.store', $ticket->id), ['body' => 'Hi'])->assertForbidden();
    $this->post(route('tickets.store'), ['title' => 'From staff'])->assertForbidden();
});

it('is only for owners and admins, and only for contacts with portal access', function (): void {
    $contact = portalContact();
    $member = UserEloquentModel::factory()->inTeam($contact->team_id)->create();
    $owner = UserEloquentModel::query()->where('current_team_id', $contact->team_id)->whereKeyNot($member->id)->sole();

    actingAs($member)->post(route('contacts.view-in-portal', $contact->id))->assertForbidden();

    $contact->update(['portal_access' => false]);

    actingAs($owner)->post(route('contacts.view-in-portal', $contact->id))->assertSessionHasErrors('portal_access');
});
