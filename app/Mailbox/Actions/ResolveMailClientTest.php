<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mom\Connectors\CouldNotReachConnector;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('reads a person\'s own mailbox as "me" with its connector\'s token', function (): void {
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create());
    rememberMailboxToken($mailbox);
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    ResolveMailClient::forMailbox($mailbox)->checkInbox();

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token')
        && $request->hasHeader('Prefer', 'IdType="ImmutableId"'));
});

it('reads a shared mailbox by its address', function (): void {
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->shared()->create(['address' => 'support@acme.test']));
    rememberMailboxToken($mailbox);
    Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    ResolveMailClient::forMailbox($mailbox)->checkInbox();

    Http::assertSentCount(1);
});

it('refuses a mailbox whose connector is gone', function (): void {
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create());
    ConnectorEloquentModel::query()->withoutGlobalScopes()->whereKey($mailbox->getConnectorId()->toString())->delete();

    expect(fn () => ResolveMailClient::forMailbox($mailbox))
        ->toThrow(CouldNotReachConnector::class, 'The account this mailbox went through is no longer connected.');
});

it('refuses a mailbox whose connector is of a type that cannot do email', function (): void {
    $connector = ConnectorEloquentModel::factory()->create(['type' => 'carrier_pigeon']);
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create(['team_id' => $connector->team_id, 'connector_id' => $connector->id]));

    expect(fn () => ResolveMailClient::forMailbox($mailbox))
        ->toThrow(CouldNotReachConnector::class, 'Carrier Pigeon accounts cannot be used for email.');
});
