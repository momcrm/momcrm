<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the portal sign-in page, filled in from the link', function (): void {
    get(route('portal.login', ['email' => 'sam@acme.test']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/auth/login')
            ->where('email', 'sam@acme.test')
            ->has('socialProviders'));
});

it('sends a signed-in contact on to the portal', function (): void {
    actingAs(ContactEloquentModel::factory()->withPortalAccess()->create(), 'portal')
        ->get(route('portal.login'))
        ->assertRedirectToRoute('portal.home');
});
