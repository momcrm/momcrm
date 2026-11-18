<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorSession;
use App\User\UserEloquentModel;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    config(['services.microsoft.client_id' => 'client-id', 'services.microsoft.client_secret' => 'secret']);
});

/**
 * The query string of the Microsoft address a response redirects to.
 *
 * @return array<string, string>
 */
function connectorSignInQuery(TestResponse $response): array
{
    $location = (string) $response->headers->get('Location');

    expect($location)->toStartWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return $query;
}

it('sends an admin to Microsoft asking for their own mailbox', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($owner)
        ->withSession([ConnectorSession::PENDING_OPTIONS => ['shared_mailboxes']])
        ->get(route('connectors.connect', 'microsoft'))
        ->assertRedirect()
        ->assertSessionHas(ConnectorSession::PENDING_OPTIONS, []);

    $query = connectorSignInQuery($response);

    expect($query['redirect_uri'])->toBe(route('connectors.callback', 'microsoft'))
        ->and($query['client_id'])->toBe('client-id')
        ->and($query['prompt'])->toBe('select_account')
        ->and($query)->not->toHaveKey('login_hint')
        ->and(explode(' ', $query['scope']))->toEqualCanonicalizing(['openid', 'profile', 'User.Read', 'offline_access', 'Mail.ReadWrite', 'Mail.Send']);
});

it('asks only for shared access when shared mailboxes are wanted', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($owner)
        ->get(route('connectors.connect', ['type' => 'microsoft', 'shared_mailboxes' => 1]))
        ->assertRedirect()
        ->assertSessionHas(ConnectorSession::PENDING_OPTIONS, ['shared_mailboxes']);

    expect(explode(' ', connectorSignInQuery($response)['scope']))
        ->toEqualCanonicalizing(['openid', 'profile', 'User.Read', 'offline_access', 'Mail.ReadWrite.Shared', 'Mail.Send.Shared']);
});

it('reconnects with what the connector already has, suggesting its account', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->withSharedMailboxes()->create([
        'team_id' => $owner->current_team_id,
        'account' => 'sam@acme.test',
    ]);

    $response = actingAs($owner)
        ->get(route('connectors.connect', ['type' => 'microsoft', 'connector' => $connector->id]))
        ->assertRedirect()
        ->assertSessionHas(ConnectorSession::PENDING_OPTIONS, ['shared_mailboxes']);

    $query = connectorSignInQuery($response);

    expect($query['login_hint'])->toBe('sam@acme.test')
        ->and(explode(' ', $query['scope']))->toContain('Mail.ReadWrite.Shared');
});

it('does not reconnect another team\'s connector', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = ConnectorEloquentModel::factory()->withSharedMailboxes()->create(['account' => 'them@elsewhere.test']);

    $response = actingAs($owner)
        ->get(route('connectors.connect', ['type' => 'microsoft', 'connector' => $theirs->id]))
        ->assertRedirect()
        ->assertSessionHas(ConnectorSession::PENDING_OPTIONS, []);

    expect(connectorSignInQuery($response))->not->toHaveKey('login_hint');
});

it('sends an admin to Google asking for lasting access to read and send mail', function (): void {
    config(['services.google.client_id' => 'google-id', 'services.google.client_secret' => 'secret']);
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($owner)
        ->get(route('connectors.connect', ['type' => 'google', 'shared_mailboxes' => 1]))
        ->assertRedirect()
        ->assertSessionHas(ConnectorSession::PENDING_OPTIONS, []);

    $location = (string) $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://accounts.google.com/o/oauth2/auth?')
        ->and($query['redirect_uri'])->toBe(route('connectors.callback', 'google'))
        ->and($query['client_id'])->toBe('google-id')
        ->and($query['access_type'])->toBe('offline')
        ->and($query['prompt'])->toBe('consent select_account')
        ->and(explode(' ', $query['scope']))->toEqualCanonicalizing([
            'openid', 'profile', 'email',
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.send',
        ]);
});

it('is not found for a type that does not exist, has no sign-in or is not set up', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->get('/settings/connectors/carrier-pigeon/connect')->assertNotFound();

    actingAs($owner)->get(route('connectors.connect', 'imap'))->assertNotFound();

    config(['services.microsoft.client_id' => null]);

    actingAs($owner)->get(route('connectors.connect', 'microsoft'))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('connectors.connect', 'microsoft'))->assertForbidden();
});
