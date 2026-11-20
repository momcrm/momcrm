<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it("lists the customers a proven address can sign in to, but not ones it can't", function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->create(['email' => 'sam@acme.test']);

    withSession([PortalSession::VERIFIED_EMAIL => 'sam@acme.test'])
        ->get(route('portal.choose'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/auth/choose')
            ->has('choices', 2)
            ->where('currentId', null));
});

it('marks who a signed-in contact is now', function (): void {
    [$current] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    actingAs($current, 'portal')
        ->get(route('portal.choose'))
        ->assertInertia(fn (Assert $page) => $page->where('currentId', $current->id));
});

it('starts again without a proven address', function (): void {
    get(route('portal.choose'))->assertRedirectToRoute('portal.login');
});
