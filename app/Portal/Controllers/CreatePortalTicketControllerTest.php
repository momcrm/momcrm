<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the form with the team's ticket types and priorities", function (): void {
    actingAs(portalContact(), 'portal')
        ->get(route('portal.tickets.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/tickets/create')
            ->has('types', 4)
            ->has('priorities', 4)
            ->where('canAttach', true));
});

it('is forbidden when contacts may not raise tickets', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->get(route('portal.tickets.create'))->assertForbidden();
});
