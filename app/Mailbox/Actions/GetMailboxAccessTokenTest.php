<?php

declare(strict_types=1);

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\travel;

beforeEach(function (): void {
    config([
        'services.microsoft.client_id' => 'client-id',
        'services.microsoft.client_secret' => 'secret',
        'services.microsoft.tenant' => 'common',
    ]);
    Http::preventStrayRequests();
});

it('exchanges the refresh token once and reuses the access token until it is about to expire', function (): void {
    $model = MailboxEloquentModel::factory()->create(['refresh_token' => 'refresh-token']);
    $mailbox = Mailbox::fromEloquentModel($model);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::sequence()
        ->push(['access_token' => 'first-token', 'expires_in' => 3600])
        ->push(['access_token' => 'second-token', 'expires_in' => 3600])]);

    expect(GetMailboxAccessToken::forMailbox($mailbox))->toBe('first-token')
        ->and(GetMailboxAccessToken::forMailbox($mailbox))->toBe('first-token');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->isForm() && $request->data() === [
        'client_id' => 'client-id',
        'client_secret' => 'secret',
        'grant_type' => 'refresh_token',
        'refresh_token' => 'refresh-token',
    ]);

    travel(59)->minutes();

    expect(GetMailboxAccessToken::forMailbox($mailbox))->toBe('second-token');
});

it('keeps the new refresh token when Microsoft replaces it', function (): void {
    $model = MailboxEloquentModel::factory()->create(['refresh_token' => 'refresh-token']);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
        'access_token' => 'access-token',
        'refresh_token' => 'newer-refresh-token',
        'expires_in' => 3600,
    ])]);

    GetMailboxAccessToken::forMailbox(Mailbox::fromEloquentModel($model));

    expect($model->refresh()->refresh_token)->toBe('newer-refresh-token');
});

it('marks the mailbox as needing a reconnect when Microsoft refuses the sign-in', function (): void {
    $model = MailboxEloquentModel::factory()->create();
    $mailbox = Mailbox::fromEloquentModel($model);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    expect(fn () => GetMailboxAccessToken::forMailbox($mailbox))
        ->toThrow(CouldNotReachMailbox::class, 'Microsoft no longer accepts the sign-in for this mailbox. Connect it again.');

    expect($model->refresh())
        ->status->toBe('needs_reconnect')
        ->last_error->toBe('Microsoft no longer accepts the sign-in for this mailbox. Connect it again.');

    expect(fn () => GetMailboxAccessToken::forMailbox($mailbox))->toThrow(CouldNotReachMailbox::class);

    Http::assertSentCount(1);
});

it('leaves the mailbox connected when the failure is not about its sign-in', function (Closure $response): void {
    $model = MailboxEloquentModel::factory()->create();
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => $response()]);

    expect(fn () => GetMailboxAccessToken::forMailbox(Mailbox::fromEloquentModel($model)))
        ->toThrow(CouldNotReachMailbox::class, 'Microsoft could not be reached. It will be tried again.');

    expect($model->refresh())
        ->status->toBe('connected')
        ->last_error->toBeNull();
})->with([
    'our client secret is refused' => [fn () => Http::response(['error' => 'invalid_client'], 401)],
    'Microsoft has a server error' => [fn () => Http::response(['error' => 'temporarily_unavailable'], 503)],
    'the connection fails' => [fn () => Http::failedConnection()],
]);
