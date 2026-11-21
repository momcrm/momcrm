<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
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
            ->where('portal.viewingAs', false)
            ->where('tickets.open_count', 0)
            ->where('owing', null)
            ->missing('auth')
            ->missing('choices'));

    expect($contact->fresh()->portal_last_seen_at)->not->toBeNull();
});

it('shows what is open and what is owing, as far as the contact may see', function (): void {
    $contact = portalContact(['is_primary' => true]);
    portalTicket($contact, ['title' => 'Printer jam']);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'sent', 'due_on' => now()->subDay()->toDateString()]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $contact->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    actingAs($contact, 'portal')
        ->get(route('portal.home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tickets.open_count', 1)
            ->where('tickets.recent.0.title', 'Printer jam')
            ->where('owing.balance_in_cents', 11000)
            ->where('owing.overdue_in_cents', 11000));
});

it('sends someone who is not signed in to sign in, and back here after', function (): void {
    get(route('portal.home'))
        ->assertRedirectToRoute('login')
        ->assertSessionHas('url.intended', route('portal.home'));
});

it('keeps staff out of the portal and contacts out of the app', function (): void {
    $contact = portalContact();
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($staff)->get(route('portal.home'))->assertRedirectToRoute('dashboard');

    auth()->forgetGuards();
    actingAs($contact, 'portal');
    // actingAs() makes the portal the default guard for the rest of the test,
    // which a real request never does.
    auth()->shouldUse('web');

    get(route('dashboard'))->assertRedirectToRoute('portal.home');
});

it('signs out a contact whose portal access was turned off', function (): void {
    $contact = portalContact(['portal_access' => false]);

    actingAs($contact, 'portal')
        ->get(route('portal.home'))
        ->assertRedirectToRoute('login')
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
