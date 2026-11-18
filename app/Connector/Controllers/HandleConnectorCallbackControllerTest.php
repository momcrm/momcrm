<?php

declare(strict_types=1);

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorSession;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SignedInUser;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    config(['services.microsoft.client_id' => 'client-id', 'services.microsoft.client_secret' => 'secret']);
    Http::preventStrayRequests();
});

/**
 * Who Microsoft says just signed in.
 *
 * @param  array<string, mixed>  $attributes
 */
function fakeConnectorSignIn(array $attributes = []): void
{
    Socialite::fake('microsoft', SignedInUser::fake([
        'id' => 'microsoft-123',
        'name' => 'Sam Lee',
        'email' => 'sam.lee@acme.onmicrosoft.test',
        'mail' => 'Sam@Acme.test',
        'token' => 'access-token',
        'refreshToken' => 'refresh-token',
        'expiresIn' => 3600,
        ...$attributes,
    ]));
}

it('connects the account that signed in, keeping its credentials encrypted', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeConnectorSignIn();

    actingAs($owner)
        ->get(route('connectors.callback', 'microsoft'))
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'sam@acme.test connected.']);

    $connector = ConnectorEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($connector)
        ->team_id->toBe($owner->current_team_id)
        ->type->toBe('microsoft')
        ->name->toBe('Sam Lee')
        ->account->toBe('sam@acme.test')
        ->status->toBe('connected')
        ->settings->toBe([])
        ->connected_by_id->toBe($owner->id)
        ->credentials->toBe(['refresh_token' => 'refresh-token'])
        ->and($connector->getRawOriginal('credentials'))->not->toContain('refresh-token')
        ->and($connector->toArray())->not->toHaveKey('credentials')
        ->and(GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($connector)))->toBe('access-token');
});

it('remembers that the sign-in covers shared mailboxes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeConnectorSignIn();

    actingAs($owner)
        ->withSession([ConnectorSession::PENDING_SHARED_MAILBOXES => true])
        ->get(route('connectors.callback', 'microsoft'))
        ->assertSessionMissing(ConnectorSession::PENDING_SHARED_MAILBOXES);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->settings)->toBe(['shared_mailboxes' => true]);
});

it('names the connector after its address when the account has no name', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeConnectorSignIn(['name' => null, 'mail' => null]);

    actingAs($owner)->get(route('connectors.callback', 'microsoft'))->assertRedirectToRoute('connectors.index');

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole())
        ->name->toBe('sam.lee@acme.onmicrosoft.test')
        ->account->toBe('sam.lee@acme.onmicrosoft.test');
});

it('reconnects the connector the team already has for the account', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
        'account' => 'sam@acme.test',
        'last_error' => 'Connect it again.',
        'credentials' => ['refresh_token' => 'stale-token'],
    ]);
    fakeConnectorSignIn();

    actingAs($owner)->get(route('connectors.callback', 'microsoft'))->assertRedirectToRoute('connectors.index');

    expect($connector->refresh())
        ->status->toBe('connected')
        ->last_error->toBeNull()
        ->credentials->toBe(['refresh_token' => 'refresh-token'])
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('lets two teams connect the same account, each with its own connector', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = ConnectorEloquentModel::factory()->create(['account' => 'sam@acme.test', 'credentials' => ['refresh_token' => 'their-token']]);
    fakeConnectorSignIn();

    actingAs($owner)->get(route('connectors.callback', 'microsoft'))->assertRedirectToRoute('connectors.index');

    expect($theirs->refresh()->credentials)->toBe(['refresh_token' => 'their-token'])
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->where('team_id', $owner->current_team_id)->count())->toBe(1);
});

it('saves nothing when the service gives no lasting access', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeConnectorSignIn(['refreshToken' => null]);

    actingAs($owner)
        ->get(route('connectors.callback', 'microsoft'))
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "Microsoft didn't give MomCRM lasting access to the account. Try connecting again.",
        ]);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('saves nothing when the sign-in is declined', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Socialite::fake('microsoft', fn () => throw new RuntimeException('access_denied'));

    actingAs($owner)
        ->get(route('connectors.callback', 'microsoft'))
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "We couldn't sign in to Microsoft. Try connecting again.",
        ]);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('saves nothing for an account with no address', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeConnectorSignIn(['email' => null, 'mail' => null]);

    actingAs($owner)
        ->get(route('connectors.callback', 'microsoft'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'That Microsoft account has no email address.']);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('is not found when the type is not set up', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config(['services.microsoft.client_id' => null]);
    fakeConnectorSignIn();

    actingAs($owner)->get(route('connectors.callback', 'microsoft'))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    fakeConnectorSignIn();

    actingAs($member)->get(route('connectors.callback', 'microsoft'))->assertForbidden();

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
