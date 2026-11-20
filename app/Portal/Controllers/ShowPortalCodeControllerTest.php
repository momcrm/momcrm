<?php

declare(strict_types=1);

use App\Portal\PortalSession;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it('asks for the code emailed to the address', function (): void {
    withSession([PortalSession::PENDING_EMAIL => 'sam@acme.test'])
        ->get(route('portal.login.code'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('portal/auth/code')->where('email', 'sam@acme.test'));
});

it('starts again without an address', function (): void {
    get(route('portal.login.code'))->assertRedirectToRoute('portal.login');
});
