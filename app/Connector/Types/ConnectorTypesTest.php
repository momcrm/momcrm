<?php

declare(strict_types=1);

use App\Connector\Types\Cloudflare\CloudflareConnector;
use App\Connector\Types\Cloudflare\CloudflareTokenConnector;
use App\Connector\Types\Google\GoogleConnector;
use App\Connector\Types\Imap\ImapConnector;
use Mom\Connectors\Capability;
use Mom\Connectors\ConnectorRegistry;
use Mom\Microsoft\MicrosoftConnector;

it('registers the app\'s own connector types, in the order they are offered', function (): void {
    $keys = array_map(fn ($type): string => $type->key(), ConnectorRegistry::instance()->all());

    expect($keys)->toBe(['microsoft', 'google', 'cloudflare', 'cloudflare-token', 'imap']);
});

it('registers email as something connected accounts can be used for, and every type as providing it', function (): void {
    $registry = ConnectorRegistry::instance();

    expect(array_map(fn (Capability $capability): array => [$capability->key, $capability->label], $registry->capabilities()))
        ->toBe([['mail', 'Email']])
        ->and(array_map(fn ($type): string => $type->key(), $registry->providing('mail')))
        ->toBe(['microsoft', 'google', 'cloudflare', 'cloudflare-token', 'imap']);
});

it('says a sign-in type is available only when its keys are set, and a type connected by a form always', function (): void {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => null]);

    expect(new GoogleConnector()->isAvailable())->toBeFalse();

    config(['services.google.client_secret' => 'secret']);

    expect(new GoogleConnector()->isAvailable())->toBeTrue()
        ->and(new ImapConnector()->isAvailable())->toBeTrue()
        ->and(new CloudflareTokenConnector()->isAvailable())->toBeTrue();
});

it('gives each type its own logo as an image', function (string $class): void {
    $logo = new $class()->logo();

    expect($logo)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr((string) $logo, 26), true))->toContain('<svg')->not->toContain('<script');
})->with([MicrosoftConnector::class, GoogleConnector::class, CloudflareConnector::class, CloudflareTokenConnector::class, ImapConnector::class]);
