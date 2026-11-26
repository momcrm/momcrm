<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareAccount;
use App\User\UserEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

const CLOUDFLARE_ACCOUNT = '0123456789abcdef0123456789abcdef';

/**
 * What an admin types in to connect a Cloudflare account.
 *
 * @return array{account_id: string, api_token: string}
 */
function cloudflareInput(): array
{
    return ['account_id' => strtoupper(CLOUDFLARE_ACCOUNT), 'api_token' => 'cloudflare-token'];
}

beforeEach(function (): void {
    Http::preventStrayRequests();
    config(['connectors.allow_private_hosts' => true]);
});

it('connects an account once its token works and the Worker is in place, keeping the token encrypted', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $worker = CloudflareAccount::workerName(CLOUDFLARE_ACCOUNT);
    Http::fake([
        CLOUDFLARE_API.'/user/tokens/verify' => Http::response(cloudflareResult(['status' => 'active'])),
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([
            ['id' => 'zone-1', 'name' => 'Acme.test', 'account' => ['id' => CLOUDFLARE_ACCOUNT, 'name' => 'Acme Pty Ltd']],
        ])),
        CLOUDFLARE_API.'/accounts/'.CLOUDFLARE_ACCOUNT."/workers/scripts/{$worker}" => Http::response(cloudflareResult(['id' => $worker])),
    ]);

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => CLOUDFLARE_ACCOUNT.' connected.']);

    $connector = ConnectorEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($connector)
        ->team_id->toBe($owner->current_team_id)
        ->type->toBe('cloudflare-token')
        ->account->toBe(CLOUDFLARE_ACCOUNT)
        ->name->toBe('Acme Pty Ltd')
        ->status->toBe('connected')
        ->settings->toBe(['account_id' => CLOUDFLARE_ACCOUNT])
        ->credentials->toBe(['api_token' => 'cloudflare-token'])
        ->and($connector->getRawOriginal('credentials'))->not->toContain('cloudflare-token');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), "/workers/scripts/{$worker}")
        && $request->hasHeader('Authorization', 'Bearer cloudflare-token')
        && str_contains($request->body(), '"main_module":"worker.js"')
        && str_contains($request->body(), '{"type":"plain_text","name":"MOMCRM_URL","text":'.json_encode(route('connectors.cloudflare.mail')).'}')
        && str_contains($request->body(), '{"type":"secret_text","name":"MOMCRM_SECRET","text":"'.CloudflareAccount::deliverySecret(CLOUDFLARE_ACCOUNT).'"}')
        && str_contains($request->body(), 'application/javascript+module')
        && str_contains($request->body(), 'async email(message, env)'));
});

it('accepts a token that belongs to the account instead of a person', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Http::fake([
        CLOUDFLARE_API.'/user/tokens/verify' => Http::response(cloudflareRefusal(1000, 'Invalid API Token'), 401),
        CLOUDFLARE_API.'/accounts/'.CLOUDFLARE_ACCOUNT.'/tokens/verify' => Http::response(cloudflareResult(['status' => 'active'])),
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'acme.test']])),
        CLOUDFLARE_API.'/accounts/'.CLOUDFLARE_ACCOUNT.'/workers/scripts/*' => Http::response(cloudflareResult([])),
    ]);

    actingAs($owner)->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())->assertSessionHasNoErrors();

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('Cloudflare');
});

it('connects nothing when Cloudflare does not accept the token', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Http::fake([
        CLOUDFLARE_API.'/*/tokens/verify' => Http::response(cloudflareRefusal(1000, 'Invalid API Token'), 401),
    ]);

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())
        ->assertSessionHasErrors(['api_token' => 'Cloudflare did not accept that API token.']);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('connects nothing when the token sees no domains in the account', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Http::fake([
        CLOUDFLARE_API.'/user/tokens/verify' => Http::response(cloudflareResult(['status' => 'active'])),
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([])),
    ]);

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())
        ->assertSessionHasErrors(['account_id' => "That token can't see any domains in this account. Check the account ID, and that the token includes the domains whose mail you want."]);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('connects nothing when the token may not put the Worker in the account', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Http::fake([
        CLOUDFLARE_API.'/user/tokens/verify' => Http::response(cloudflareResult(['status' => 'active'])),
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'acme.test']])),
        CLOUDFLARE_API.'/accounts/'.CLOUDFLARE_ACCOUNT.'/workers/scripts/*' => Http::response(cloudflareRefusal(10000, 'Authentication error'), 403),
    ]);

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())
        ->assertSessionHasErrors(['api_token' => 'MomCRM could not put its Worker in the account. Check that the token has Workers Scripts (Edit).']);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('connects nothing when Cloudflare could not reach this installation to hand mail over', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config(['connectors.allow_private_hosts' => false]);
    Http::fake();

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), cloudflareInput())
        ->assertSessionHasErrors('account_id');

    Http::assertNothingSent();
    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('rejects an account ID that is not one', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Http::fake();

    actingAs($owner)
        ->post(route('connectors.store', 'cloudflare-token'), ['account_id' => 'acme.test', 'api_token' => 'cloudflare-token'])
        ->assertSessionHasErrors(['account_id' => 'An account ID is 32 letters and numbers, with nothing around it.']);

    Http::assertNothingSent();
});

it('names its Worker and its secret after the account, the same every time', function (): void {
    expect(CloudflareAccount::workerName(CLOUDFLARE_ACCOUNT))
        ->toMatch('/^momcrm-mail-[a-f0-9]{12}$/')
        ->toBe(CloudflareAccount::workerName(CLOUDFLARE_ACCOUNT))
        ->not->toBe(CloudflareAccount::workerName(str_repeat('a', 32)))
        ->and(CloudflareAccount::deliverySecret(CLOUDFLARE_ACCOUNT))
        ->toMatch('/^[a-f0-9]{64}$/')
        ->not->toBe(CloudflareAccount::deliverySecret(str_repeat('a', 32)));
});
