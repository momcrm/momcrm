<?php

declare(strict_types=1);

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MicrosoftGraph;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('reads a person\'s own mailbox as "me" with the mailbox\'s token', function (): void {
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create());
    GetMailboxAccessToken::remember($mailbox, 'access-token', 3600);
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    expect(MicrosoftGraph::forMailbox($mailbox)->inbox())->toBe(['id' => 'inbox-id']);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token')
        && $request->hasHeader('Prefer', 'IdType="ImmutableId"'));
});

it('reads a shared mailbox by its address', function (): void {
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->shared()->create(['address' => 'support@acme.test']));
    GetMailboxAccessToken::remember($mailbox, 'access-token', 3600);
    Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    expect(MicrosoftGraph::forMailbox($mailbox)->inbox())->toBe(['id' => 'inbox-id']);
});

it('passes on why Microsoft refused', function (): void {
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403)]);

    expect(fn () => MicrosoftGraph::withToken('access-token')->inbox())
        ->toThrow(CouldNotReachMailbox::class, 'Access is denied.');
});

it('asks for a reconnect when the token is no longer accepted', function (): void {
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['error' => ['code' => 'InvalidAuthenticationToken']], 401)]);

    expect(fn () => MicrosoftGraph::withToken('access-token')->inbox())
        ->toThrow(CouldNotReachMailbox::class, 'Microsoft no longer accepts the sign-in for this mailbox. Connect it again.');
});

it('tries again when Microsoft is busy, then reads the mailbox', function (): void {
    Sleep::fake();
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::sequence()
        ->push(['error' => ['code' => 'serviceNotAvailable']], 503)
        ->push(['id' => 'inbox-id'])]);

    expect(MicrosoftGraph::withToken('access-token')->inbox())->toBe(['id' => 'inbox-id']);

    Http::assertSentCount(2);
});

it('gives up for now when Microsoft stays unavailable', function (Closure $response): void {
    Sleep::fake();
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => $response()]);

    expect(fn () => MicrosoftGraph::withToken('access-token')->inbox())
        ->toThrow(CouldNotReachMailbox::class, 'Microsoft could not be reached. It will be tried again.');

    Http::assertSentCount(3);
})->with([
    'a server error' => [fn () => Http::response(['error' => ['code' => 'serviceNotAvailable']], 503)],
    'throttling' => [fn () => Http::response(['error' => ['code' => 'TooManyRequests']], 429)],
    'no connection' => [fn () => Http::failedConnection()],
]);
