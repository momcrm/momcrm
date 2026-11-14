<?php

declare(strict_types=1);

use Laravel\Socialite\Socialite;

use function Pest\Laravel\get;

it('sends people to a configured provider', function (): void {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
    Socialite::fake('google');

    get(route('auth.social.redirect', 'google'))->assertRedirect();
});

it('does not offer providers without keys or that do not exist', function (string $provider): void {
    get(route('auth.social.redirect', $provider))->assertNotFound();
})->with(['microsoft', 'chatgpt']);
