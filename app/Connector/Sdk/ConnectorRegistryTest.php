<?php

declare(strict_types=1);

use App\Connector\ConnectorCapabilityEnum;
use App\Connector\Sdk\ConnectorRegistry;
use App\Connector\Sdk\UnknownConnectorType;
use App\Connector\Types\Google\GoogleConnector;
use App\Connector\Types\Imap\ImapConnector;
use App\Connector\Types\Microsoft\MicrosoftConnector;

it('knows the app\'s own connector types, in the order they are offered', function (): void {
    $keys = array_map(fn ($type): string => $type->key(), ConnectorRegistry::instance()->all());

    expect($keys)->toBe(['microsoft', 'google', 'imap']);
});

it('finds a type by its key', function (): void {
    $types = new ConnectorRegistry(new MicrosoftConnector, new GoogleConnector);

    expect($types->find('google'))->toBeInstanceOf(GoogleConnector::class)
        ->and($types->find('imap'))->toBeNull()
        ->and($types->find(null))->toBeNull();
});

it('takes a new type, and replaces one registered under the same key', function (): void {
    $types = new ConnectorRegistry(new MicrosoftConnector);
    $imap = new ImapConnector;

    $types->register($imap);
    $types->register(new ImapConnector);

    expect($types->all())->toHaveCount(2)
        ->and($types->find('imap'))->not->toBe($imap);
});

it('stands in for a type that is not installed with one that can do nothing', function (): void {
    $type = new ConnectorRegistry()->get('carrier_pigeon');

    expect($type)->toBeInstanceOf(UnknownConnectorType::class)
        ->and($type->key())->toBe('carrier_pigeon')
        ->and($type->label())->toBe('Carrier Pigeon')
        ->and($type->isAvailable())->toBeFalse()
        ->and($type->capabilities())->toBe([])
        ->and($type->logo())->toBeNull()
        ->and($type->toCatalogItem()['connection'])->toBeNull();
});

it('works out what a type can be used for from the contracts it implements', function (): void {
    expect(new MicrosoftConnector()->capabilities())->toBe([ConnectorCapabilityEnum::Mail])
        ->and(new MicrosoftConnector()->provides(ConnectorCapabilityEnum::Mail))->toBeTrue();
});

it('says a sign-in type is available only when its keys are set', function (): void {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => null]);

    expect(new GoogleConnector()->isAvailable())->toBeFalse();

    config(['services.google.client_secret' => 'secret']);

    expect(new GoogleConnector()->isAvailable())->toBeTrue()
        ->and(new ImapConnector()->isAvailable())->toBeTrue();
});

it('hands the page each type\'s own logo as an image', function (string $class): void {
    $logo = new $class()->logo();

    expect($logo)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr((string) $logo, 26), true))->toContain('<svg')->not->toContain('<script');
})->with([MicrosoftConnector::class, GoogleConnector::class, ImapConnector::class]);
