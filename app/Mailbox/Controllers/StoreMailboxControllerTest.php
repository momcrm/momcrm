<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareAccount;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeSecond;

beforeEach(function (): void {
    Http::preventStrayRequests();
});

/**
 * A working connector in the owner's team, with a token that is still good.
 *
 * @param  array<string, mixed>  $attributes
 */
function mailConnector(UserEloquentModel $owner, array $attributes = [], bool $withSharedMailboxes = false): ConnectorEloquentModel
{
    $factory = ConnectorEloquentModel::factory();
    $connector = ($withSharedMailboxes ? $factory->withSharedMailboxes() : $factory)->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Sam Lee',
        'account' => 'sam@acme.test',
        ...$attributes,
    ]);

    rememberMailboxToken(MailboxEloquentModel::factory()->make(['connector_id' => $connector->id]));

    return $connector;
}

it('adds the connected account\'s own mailbox once its inbox opens', function (): void {
    $now = freezeSecond();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = mailConnector($owner);
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->post(route('mailboxes.store'), ['connector_id' => $connector->id])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'sam@acme.test added.']);

    $mailbox = MailboxEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($mailbox)
        ->team_id->toBe($owner->current_team_id)
        ->connector_id->toBe($connector->id)
        ->address->toBe('sam@acme.test')
        ->display_name->toBe('Sam Lee')
        ->is_shared->toBeFalse()
        ->auto_create_tickets->toBeTrue()
        ->and($mailbox->synced_through?->equalTo($now))->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token'));
});

it('adds a shared mailbox the account can open', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = mailConnector($owner, withSharedMailboxes: true);
    Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => ' Support@Acme.test '])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test added.']);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->sole())
        ->address->toBe('support@acme.test')
        ->display_name->toBeNull()
        ->is_shared->toBeTrue();
});

it('treats the account\'s own address as its own mailbox', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = mailConnector($owner);
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'SAM@acme.test'])
        ->assertSessionHasNoErrors();

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->sole()->is_shared)->toBeFalse();
});

it('adds nothing and says why', function (Closure $setUp, array $errors): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $input = $setUp($owner);
    $before = MailboxEloquentModel::query()->withoutGlobalScopes()->count();

    actingAs($owner)->post(route('mailboxes.store'), $input)->assertSessionHasErrors($errors);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe($before);
})->with([
    'no connector chosen' => [
        fn () => [],
        ['connector_id' => 'The connector id field is required.'],
    ],
    'another team\'s connector' => [
        fn () => ['connector_id' => ConnectorEloquentModel::factory()->create()->id],
        ['connector_id' => 'Choose one of your connected accounts.'],
    ],
    'a connector that needs reconnecting' => [
        fn (UserEloquentModel $owner) => ['connector_id' => ConnectorEloquentModel::factory()->needsReconnect()->create(['team_id' => $owner->current_team_id, 'account' => 'sam@acme.test'])->id],
        ['connector_id' => 'sam@acme.test needs reconnecting before a mailbox can be added.'],
    ],
    'an address that is not one' => [
        fn (UserEloquentModel $owner) => ['connector_id' => mailConnector($owner)->id, 'address' => 'support'],
        ['address' => 'The address field must be a valid email address.'],
    ],
    'a shared mailbox through a connector without shared access' => [
        fn (UserEloquentModel $owner) => ['connector_id' => mailConnector($owner)->id, 'address' => 'support@acme.test'],
        ['address' => 'sam@acme.test was connected without shared mailboxes. Reconnect it with them turned on to add one.'],
    ],
    'a mailbox the team already has' => [
        function (UserEloquentModel $owner): array {
            MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'sam@acme.test']);

            return ['connector_id' => mailConnector($owner)->id];
        },
        ['address' => 'sam@acme.test is already one of your mailboxes.'],
    ],
    'a mailbox another workspace has' => [
        function (UserEloquentModel $owner): array {
            MailboxEloquentModel::factory()->create(['address' => 'sam@acme.test']);

            return ['connector_id' => mailConnector($owner)->id];
        },
        ['address' => 'sam@acme.test is already connected to another workspace.'],
    ],
    'an inbox that will not open' => [
        function (UserEloquentModel $owner): array {
            Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403)]);

            return ['connector_id' => mailConnector($owner, withSharedMailboxes: true)->id, 'address' => 'support@acme.test'];
        },
        ['address' => "We couldn't open the inbox of support@acme.test. Check the address, and that sam@acme.test has access to it."],
    ],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $connector = mailConnector($owner);

    actingAs($member)->post(route('mailboxes.store'), ['connector_id' => $connector->id])->assertForbidden();

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

describe('through a Cloudflare account', function (): void {
    /**
     * A Cloudflare account in the owner's team, connected with a token that covers helpdesk.test.
     */
    function cloudflareConnector(UserEloquentModel $owner): ConnectorEloquentModel
    {
        return ConnectorEloquentModel::factory()->cloudflareToken()->create(['team_id' => $owner->current_team_id, 'name' => 'Acme Pty Ltd']);
    }

    /**
     * Cloudflare with the domain helpdesk.test in the account and the
     * routing rules it has for it, reachable from this installation.
     *
     * @param  list<array<string, mixed>>  $rules
     * @param  array<string, mixed>  $more
     */
    function fakeCloudflareRouting(array $rules = [], array $more = []): void
    {
        config(['connectors.allow_private_hosts' => true]);

        Http::fake([
            CLOUDFLARE_API.'/accounts/*/workers/scripts/*' => Http::response(cloudflareResult(['id' => 'worker'])),
            CLOUDFLARE_API.'/zones?*name=helpdesk.test*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing' => Http::response(cloudflareResult(['enabled' => true, 'status' => 'ready'])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules?*' => Http::response(cloudflareResult($rules)),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules' => Http::response(cloudflareResult(['id' => 'rule-1'])),
            ...$more,
        ]);
    }

    it('adds an address once the Worker is in the account and Cloudflare hands the address\'s mail to it', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting();

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'Support@Mail.Helpdesk.test'])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@mail.helpdesk.test added.']);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->sole())
            ->connector_id->toBe($connector->id)
            ->address->toBe('support@mail.helpdesk.test')
            ->auto_create_tickets->toBeTrue();

        $worker = CloudflareAccount::workerName($connector->account);

        Http::assertSentInOrder([
            fn (Request $request): bool => str_contains($request->url(), '/zones?'),
            fn (Request $request): bool => str_contains($request->url(), '/zones?'),
            fn (Request $request): bool => str_ends_with($request->url(), '/zones/zone-1/email/routing'),
            fn (Request $request): bool => str_contains($request->url(), '/zones/zone-1/email/routing/rules?'),
            fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), "/accounts/{$connector->account}/workers/scripts/{$worker}"),
            fn (Request $request): bool => $request->method() === 'POST' && str_ends_with($request->url(), '/zones/zone-1/email/routing/rules'),
        ]);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/zones/zone-1/email/routing/rules')
            && $request->hasHeader('Authorization', 'Bearer cloudflare-token')
            && $request->data() === [
                'name' => 'MomCRM: support@mail.helpdesk.test',
                'enabled' => true,
                'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => 'support@mail.helpdesk.test']],
                'actions' => [['type' => 'worker', 'value' => [$worker]]],
            ]);
    });

    it('adds an address that Cloudflare already hands to the Worker without another rule, putting the Worker there again', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting([[
            'id' => 'rule-1',
            'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => 'support@helpdesk.test']],
            'actions' => [['type' => 'worker', 'value' => [CloudflareAccount::workerName($connector->account)]]],
        ]]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasNoErrors();

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
    });

    it('asks for an address, because the account has no mailbox of its own', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        Http::fake();

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id])
            ->assertSessionHasErrors(['address' => 'Enter the address to take mail for, such as support@yourcompany.com.']);

        Http::assertNothingSent();
        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    });

    it('leaves an address alone when Cloudflare already sends its mail somewhere else', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting([[
            'id' => 'rule-1',
            'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => 'support@helpdesk.test']],
            'actions' => [['type' => 'forward', 'value' => ['sam@acme.test']]],
        ]]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasErrors(['address' => "Cloudflare already sends support@helpdesk.test's mail somewhere else. Remove that routing rule in Cloudflare, then add the mailbox again."]);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
    });

    it('adds nothing for a domain that is not in the account', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting();

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@elsewhere.test'])
            ->assertSessionHasErrors(['address' => 'elsewhere.test is not one of the domains MomCRM can see in this Cloudflare account.']);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    });

    it('adds nothing while Email Routing is turned off for the domain', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting(more: [
            CLOUDFLARE_API.'/zones/zone-1/email/routing' => Http::response(cloudflareResult(['enabled' => false, 'status' => 'unconfigured'])),
        ]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasErrors(['address' => 'Email Routing is not turned on for helpdesk.test. Turn it on in Cloudflare, under Email Service, then add the mailbox again.']);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    });

    it('adds the address when the token may not look at the domain\'s Email Routing settings', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        fakeCloudflareRouting(more: [
            CLOUDFLARE_API.'/zones/zone-1/email/routing' => Http::response(cloudflareRefusal(10000, 'Authentication error'), 403),
        ]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasNoErrors();

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
    });

    it('says to try again when Cloudflare cannot be reached', function (): void {
        $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
        $connector = cloudflareConnector($owner);
        Http::fake([CLOUDFLARE_API.'/*' => Http::response(status: 503)]);

        actingAs($owner)
            ->post(route('mailboxes.store'), ['connector_id' => $connector->id, 'address' => 'support@helpdesk.test'])
            ->assertSessionHasErrors(['address' => 'Cloudflare could not be reached. Try again in a moment.']);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    });
});
