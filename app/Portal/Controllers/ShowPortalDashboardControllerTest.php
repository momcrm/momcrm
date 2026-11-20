<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows a contact their portal with the sections the rules allow', function (): void {
    $contact = portalContact();

    actingAs($contact, 'portal')
        ->get(route('portal.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/dashboard')
            ->where('portal.contact.name', 'Mark Ellis')
            ->where('portal.customer.name', 'Coastal Logistics')
            ->where('portal.sections.tickets', true)
            ->where('portal.sections.invoices', false)
            ->where('portal.canSwitch', false)
            ->missing('auth')
            ->missing('choices'));

    expect($contact->fresh()->portal_last_seen_at)->not->toBeNull();
});

it('sends someone who is not signed in to the portal sign-in page', function (): void {
    get(route('portal.home'))->assertRedirectToRoute('portal.login');
});

it('keeps staff out of the portal and contacts out of the app', function (): void {
    $contact = portalContact();
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($staff)->get(route('portal.home'))->assertRedirectToRoute('portal.login');

    auth()->forgetGuards();
    actingAs($contact, 'portal');
    // actingAs() makes the portal the default guard for the rest of the test,
    // which a real request never does.
    auth()->shouldUse('web');

    get(route('dashboard'))->assertRedirectToRoute('login');
});

it('signs out a contact whose portal access was turned off', function (): void {
    $contact = portalContact(['portal_access' => false]);

    actingAs($contact, 'portal')
        ->get(route('portal.home'))
        ->assertRedirectToRoute('portal.login')
        ->assertSessionHasErrors('email');

    expect(auth('portal')->check())->toBeFalse();
});

it('shows no sections once the team has no contact rules', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->delete();

    actingAs($contact, 'portal')
        ->get(route('portal.home'))
        ->assertInertia(fn (Assert $page) => $page->where('portal.sections', fn ($sections): bool => collect($sections)->every(fn (bool $shown): bool => ! $shown)));
});
