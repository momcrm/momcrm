<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
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
