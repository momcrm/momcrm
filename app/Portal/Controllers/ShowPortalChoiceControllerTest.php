<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it("lists the customers a proven address can sign in to, but not ones it can't", function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->create(['email' => 'sam@acme.test']);

    withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->get(route('portal.choose'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/auth/choose')
            ->has('choices', 2)
            ->where('workspace', null)
            ->where('currentId', null));
});

it('offers the workspace when the address is staff too', function (): void {
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test', 'name' => 'Sam Lee']);
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->get(route('portal.choose'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('choices', 1)
            ->where('workspace.name', 'Sam Lee')
            ->where('isInWorkspace', false));
});

it('lets signed-in staff with a verified address open their portal', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    actingAs($staff)
        ->get(route('portal.choose'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('choices', 1)
            ->where('isInWorkspace', true));
});

it('marks who a signed-in contact is now', function (): void {
    [$current] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    actingAs($current, 'portal')
        ->withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->get(route('portal.choose'))
        ->assertInertia(fn (Assert $page) => $page->where('currentId', $current->id));
});

it("goes by the address proven at sign-in, not the contact's address on record", function (): void {
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'kim@acme.test']);
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'kim@acme.test']);

    actingAs($contact, 'portal')
        ->withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->get(route('portal.choose'))
        ->assertRedirectToRoute('login');
});

it('offers nothing to staff viewing the portal as a contact', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    actingAs($staff)
        ->actingAs($contact, 'portal')
        ->withSession([PortalSession::VIEWING_AS => $staff->id])
        ->get(route('portal.choose'))
        ->assertRedirectToRoute('login');
});

it('starts again without a proven address', function (): void {
    get(route('portal.choose'))->assertRedirectToRoute('login');
});
