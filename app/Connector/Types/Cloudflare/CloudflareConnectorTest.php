<?php

declare(strict_types=1);

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareAccount;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SignedInUser;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\OutgoingMail;

use function Pest\Laravel\actingAs;

const SIGNED_IN_ACCOUNT = 'fedcba9876543210fedcba9876543210';

beforeEach(function (): void {
    config(['services.cloudflare.client_id' => 'client-id', 'services.cloudflare.client_secret' => 'client-secret']);
    Http::preventStrayRequests();
});

/**
 * A mailbox at support@helpdesk.test through a Cloudflare account that was
 * signed in to, with access that is still good.
 */
function signedInCloudflareMailbox(): Mailbox
{
    $connector = ConnectorEloquentModel::factory()->cloudflare()->create(['account' => SIGNED_IN_ACCOUNT]);
    $mailbox = MailboxEloquentModel::factory()->create([
        'team_id' => $connector->team_id,
        'connector_id' => $connector->id,
        'address' => 'support@helpdesk.test',
        'is_shared' => true,
    ]);
    rememberMailboxToken($mailbox, 'signed-in-token');

    return Mailbox::fromEloquentModel($mailbox);
}

describe('signing in', function (): void {
    it('sends an admin to Cloudflare, asking only for what handing mail over and replying need', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

        $location = (string) actingAs($owner)
            ->get(route('connectors.connect', 'cloudflare'))
            ->assertRedirect()
            ->headers->get('Location');

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        expect($location)->toStartWith('https://dash.cloudflare.com/oauth2/auth?')
            ->and($query)->toMatchArray([
                'client_id' => 'client-id',
                'redirect_uri' => route('connectors.callback', 'cloudflare'),
                'response_type' => 'code',
                'scope' => 'workers-scripts.admin email-routing-rule.read email-routing-rule.write email-sending.write zone.read zone-settings.read offline_access',
                'code_challenge_method' => 'S256',
            ])
            ->and($query)->toHaveKeys(['state', 'code_challenge']);
    });

    it('is not offered on an installation with no OAuth client at Cloudflare', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        config(['services.cloudflare.client_secret' => null]);

        actingAs($owner)->get(route('connectors.connect', 'cloudflare'))->assertNotFound();
        actingAs($owner)->get(route('connectors.callback', 'cloudflare'))->assertNotFound();
    });

    it('connects the Cloudflare account that was picked, known by its id', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        Socialite::fake('cloudflare', SignedInUser::fake([
            'id' => SIGNED_IN_ACCOUNT,
            'name' => 'Acme Pty Ltd',
            'account' => strtoupper(SIGNED_IN_ACCOUNT),
            'token' => 'signed-in-token',
            'refreshToken' => 'refresh-token',
            'expiresIn' => 3600,
        ]));

        actingAs($owner)
            ->get(route('connectors.callback', 'cloudflare'))
            ->assertRedirectToRoute('connectors.index')
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => SIGNED_IN_ACCOUNT.' connected.']);

        $connector = ConnectorEloquentModel::query()->withoutGlobalScopes()->sole();

        expect($connector)
            ->team_id->toBe($owner->current_team_id)
            ->type->toBe('cloudflare')
            ->account->toBe(SIGNED_IN_ACCOUNT)
            ->name->toBe('Acme Pty Ltd')
            ->status->toBe('connected')
            ->credentials->toBe(['refresh_token' => 'refresh-token'])
            ->and(Connector::fromEloquentModel($connector)->hasOwnMailbox())->toBeFalse()
            ->and(GetConnectorAccessToken::forConnector(Connector::fromEloquentModel($connector)))->toBe('signed-in-token');
    });

    it('takes the account from what the sign-in can see', function (array $accounts, array $zones, ?string $account, ?string $name): void {
        Http::fake([
            CLOUDFLARE_API.'/accounts?*' => Http::response($accounts['status'] === 200 ? cloudflareResult($accounts['result']) : cloudflareRefusal(10000, 'Authentication error'), $accounts['status']),
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult($zones)),
        ]);

        $signIn = fn () => Socialite::driver('cloudflare')->userFromToken('signed-in-token');

        if ($account === null) {
            expect($signIn)->toThrow(RuntimeException::class);

            return;
        }

        expect($signIn())
            ->getId()->toBe($account)
            ->getName()->toBe($name)
            ->getEmail()->toBeNull()
            ->getRaw()->toBe(['account' => $account, 'name' => $name]);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer signed-in-token'));
    })->with([
        'the first account it lists' => [
            ['status' => 200, 'result' => [['id' => SIGNED_IN_ACCOUNT, 'name' => 'Acme Pty Ltd'], ['id' => str_repeat('b', 32), 'name' => 'Other']]],
            [],
            SIGNED_IN_ACCOUNT,
            'Acme Pty Ltd',
        ],
        'the account of a domain, when it may not list accounts' => [
            ['status' => 403, 'result' => []],
            [['id' => 'zone-1', 'name' => 'helpdesk.test', 'account' => ['id' => SIGNED_IN_ACCOUNT, 'name' => 'Acme Pty Ltd']]],
            SIGNED_IN_ACCOUNT,
            'Acme Pty Ltd',
        ],
        'no account at all' => [['status' => 200, 'result' => []], [], null, null],
    ]);
});

describe('working through the sign-in', function (): void {
    it('asks Cloudflare with the access the sign-in gave', function (): void {
        $mailbox = signedInCloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/accounts/'.SIGNED_IN_ACCOUNT.'/email/sending/send' => Http::response(cloudflareResult(['message_id' => '<sent-9@helpdesk.test>', 'delivered' => ['sam@acme.test']])),
        ]);

        ResolveMailClient::forMailbox($mailbox)->send(new OutgoingMail(['sam@acme.test'], 'We are on it.', 'Printer offline', '[#12]'));

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer signed-in-token'));
    });

    it('asks for the account to be connected again when Cloudflare no longer knows the sign-in', function (): void {
        $mailbox = signedInCloudflareMailbox();
        Http::fake([CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareRefusal(10101, 'email.sending.error.authentication.unauthorized'), 401)]);

        expect(fn () => ResolveMailClient::forMailbox($mailbox)->send(new OutgoingMail(['sam@acme.test'], 'We are on it.', 'Printer offline', '[#12]')))
            ->toThrow(CouldNotReachConnector::class, 'Cloudflare no longer accepts the sign-in for this connector. Connect it again.');

        Http::assertSentCount(1);
        expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('needs_reconnect');
    });

    it('names what Cloudflare refused when the sign-in does not cover it', function (): void {
        $mailbox = signedInCloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules?*' => Http::response(cloudflareRefusal(10000, 'Authentication error'), 403),
        ]);

        expect(fn () => ResolveMailClient::forMailbox($mailbox)->checkInbox())
            ->toThrow(CouldNotReachConnector::class, "Cloudflare did not let MomCRM list the domain's routing rules. Connect the account again and agree to everything MomCRM asks for.");

        expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('connected');
    });

    it('puts the Worker in the account when the first mailbox is added, since signing in does not', function (): void {
        config(['connectors.allow_private_hosts' => true]);
        $connector = ConnectorEloquentModel::factory()->cloudflare()->create(['account' => SIGNED_IN_ACCOUNT]);
        $owner = UserEloquentModel::factory()->inTeam($connector->team_id, TeamRoleEnum::Owner)->create();
        rememberMailboxToken(MailboxEloquentModel::factory()->make(['connector_id' => $connector->id]), 'signed-in-token');
        $worker = CloudflareAccount::workerName(SIGNED_IN_ACCOUNT);
        Http::fake([
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing' => Http::response(cloudflareResult(['enabled' => true])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules?*' => Http::response(cloudflareResult([])),
            CLOUDFLARE_API.'/accounts/'.SIGNED_IN_ACCOUNT."/workers/scripts/{$worker}" => Http::response(cloudflareResult(['id' => $worker])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules' => Http::response(cloudflareResult(['id' => 'rule-1'])),
        ]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasNoErrors();

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->sole()->address)->toBe('support@helpdesk.test');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), "/workers/scripts/{$worker}")
            && $request->hasHeader('Authorization', 'Bearer signed-in-token')
            && str_contains($request->body(), '"name":"MOMCRM_SECRET","text":"'.CloudflareAccount::deliverySecret(SIGNED_IN_ACCOUNT).'"'));
    });

    it('adds no mailbox on an installation Cloudflare could not reach to hand mail over', function (): void {
        $connector = ConnectorEloquentModel::factory()->cloudflare()->create(['account' => SIGNED_IN_ACCOUNT]);
        $owner = UserEloquentModel::factory()->inTeam($connector->team_id, TeamRoleEnum::Owner)->create();
        rememberMailboxToken(MailboxEloquentModel::factory()->make(['connector_id' => $connector->id]), 'signed-in-token');
        Http::fake([
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing' => Http::response(cloudflareResult(['enabled' => true])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules?*' => Http::response(cloudflareResult([])),
        ]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasErrors('address');

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
    });
});
