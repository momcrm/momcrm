<?php

declare(strict_types=1);

use App\Portal\PortalSession;

use function Pest\Laravel\get;

it('sends the contact to the provider, marked as a portal sign-in', function (): void {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);

    get(route('portal.social.redirect', 'google'))
        ->assertRedirect()
        ->assertSessionHas(PortalSession::SOCIAL, true);
});

it('does not handle providers without keys', function (): void {
    config(['services.google.client_id' => null]);

    get(route('portal.social.redirect', 'google'))->assertNotFound();
});
