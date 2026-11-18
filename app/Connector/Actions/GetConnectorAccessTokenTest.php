<?php

declare(strict_types=1);

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Exceptions\CouldNotReachConnector;
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
    $model = ConnectorEloquentModel::factory()->create(['credentials' => ['refresh_token' => 'refresh-token']]);
    $connector = Connector::fromEloquentModel($model);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::sequence()
        ->push(['access_token' => 'first-token', 'expires_in' => 3600])
        ->push(['access_token' => 'second-token', 'expires_in' => 3600])]);

    expect(GetConnectorAccessToken::forConnector($connector))->toBe('first-token')
        ->and(GetConnectorAccessToken::forConnector($connector))->toBe('first-token');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->isForm() && $request->data() === [
        'client_id' => 'client-id',
        'client_secret' => 'secret',
        'grant_type' => 'refresh_token',
        'refresh_token' => 'refresh-token',
    ]);

    travel(59)->minutes();

    expect(GetConnectorAccessToken::forConnector($connector))->toBe('second-token');
});

it('keeps the new refresh token when the service replaces it', function (): void {
    $model = ConnectorEloquentModel::factory()->create(['credentials' => ['refresh_token' => 'refresh-token']]);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
        'access_token' => 'access-token',
        'refresh_token' => 'newer-refresh-token',
        'expires_in' => 3600,
    ])]);

    GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($model));

    expect($model->refresh()->credentials)->toBe(['refresh_token' => 'newer-refresh-token']);
});

it('marks the connector as needing a reconnect when the service refuses the sign-in', function (): void {
    $model = ConnectorEloquentModel::factory()->create();
    $connector = Connector::fromEloquentModel($model);
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    expect(fn () => GetConnectorAccessToken::forConnector($connector))
        ->toThrow(CouldNotReachConnector::class, 'Microsoft no longer accepts the sign-in for this connector. Connect it again.');

    expect($model->refresh())
        ->status->toBe('needs_reconnect')
        ->last_error->toBe('Microsoft no longer accepts the sign-in for this connector. Connect it again.');

    expect(fn () => GetConnectorAccessToken::forConnector($connector))->toThrow(CouldNotReachConnector::class);

    Http::assertSentCount(1);
});

it('leaves the connector connected when the failure is not about its sign-in', function (Closure $response): void {
    $model = ConnectorEloquentModel::factory()->create();
    Http::fake(['login.microsoftonline.com/common/oauth2/v2.0/token' => $response()]);

    expect(fn () => GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($model)))
        ->toThrow(CouldNotReachConnector::class, 'Microsoft could not be reached. It will be tried again.');

    expect($model->refresh())
        ->status->toBe('connected')
        ->last_error->toBeNull();
})->with([
    'our client secret is refused' => [fn () => Http::response(['error' => 'invalid_client'], 401)],
    'Microsoft has a server error' => [fn () => Http::response(['error' => 'temporarily_unavailable'], 503)],
    'the connection fails' => [fn () => Http::failedConnection()],
]);

it('exchanges a Google connector\'s refresh token at Google, with Google\'s keys', function (): void {
    config(['services.google.client_id' => 'google-id', 'services.google.client_secret' => 'google-secret']);
    $model = ConnectorEloquentModel::factory()->google()->create(['credentials' => ['refresh_token' => 'google-refresh-token']]);
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token', 'expires_in' => 3599])]);

    expect(GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($model)))->toBe('google-access-token');

    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'client_id' => 'google-id',
        'client_secret' => 'google-secret',
        'grant_type' => 'refresh_token',
        'refresh_token' => 'google-refresh-token',
    ]);
});

it('names Google when Google refuses the sign-in', function (): void {
    config(['services.google.client_id' => 'google-id', 'services.google.client_secret' => 'google-secret']);
    $model = ConnectorEloquentModel::factory()->google()->create();
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

    expect(fn () => GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($model)))
        ->toThrow(CouldNotReachConnector::class, 'Google no longer accepts the sign-in for this connector. Connect it again.');

    expect($model->refresh()->status)->toBe('needs_reconnect');
});

it('asks nothing of the service for a connector with no refresh token', function (): void {
    $model = ConnectorEloquentModel::factory()->create(['credentials' => []]);

    expect(fn () => GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($model)))
        ->toThrow(CouldNotReachConnector::class, 'Microsoft no longer accepts the sign-in for this connector. Connect it again.');

    Http::assertNothingSent();
});
