<?php

declare(strict_types=1);

use App\Portal\PortalSession;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it("serves a signed-in contact the portal's page", function (): void {
    actingAs(portalContact(), 'portal')
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/tickets/index')
            ->has('portal')
            ->missing('auth'));
});

it("serves staff the app's page at the same address", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/index')
            ->has('auth.user')
            ->missing('portal'));
});

it("keeps the app's own middleware for staff", function (): void {
    get(route('tickets.index'))->assertRedirectToRoute('login');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->notOnboarded()->create())
        ->get(route('tickets.index'))
        ->assertRedirectToRoute('onboarding.about');
});

it("keeps the portal's own middleware for contacts", function (): void {
    actingAs(portalContact(['portal_access' => false]), 'portal')
        ->get(route('tickets.index'))
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    assertGuest('portal');
});

it('serves a staff member viewing as a contact the portal, read-only', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = portalContact();

    actingAs($staff)
        ->actingAs($contact, 'portal')
        ->withSession([PortalSession::VIEWING_AS => $staff->id])
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/tickets/index')
            ->where('portal.viewingAs', true));
});

it('signs a contact out at the same address staff use', function (): void {
    actingAs(portalContact(), 'portal')
        ->post(route('logout'))
        ->assertRedirectToRoute('login');

    assertGuest('portal');
});

it('signs staff out as before', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('logout'))
        ->assertRedirect('/');

    assertGuest('web');
});
