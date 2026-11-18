<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MailboxSession;
use App\User\UserEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as MicrosoftUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeSecond;

beforeEach(function (): void {
    config(['services.microsoft.client_id' => 'client-id', 'services.microsoft.client_secret' => 'secret']);
});

/**
 * Who Microsoft says just signed in.
 *
 * @param  array<string, mixed>  $attributes
 */
function fakeMicrosoftSignIn(array $attributes = []): void
{
    Socialite::fake('microsoft', MicrosoftUser::fake([
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

it('connects the signed-in person\'s own mailbox once its inbox opens', function (): void {
    Http::preventStrayRequests();
    $now = freezeSecond();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeMicrosoftSignIn();
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->get(route('mailboxes.callback'))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'sam@acme.test connected.']);

    $mailbox = MailboxEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($mailbox)
        ->team_id->toBe($owner->current_team_id)
        ->address->toBe('sam@acme.test')
        ->display_name->toBe('Sam Lee')
        ->is_shared->toBeFalse()
        ->status->toBe('connected')
        ->auto_create_tickets->toBeTrue()
        ->connected_by_id->toBe($owner->id)
        ->refresh_token->toBe('refresh-token')
        ->and($mailbox->synced_through?->equalTo($now))->toBeTrue()
        ->and($mailbox->getRawOriginal('refresh_token'))->not->toContain('refresh-token')
        ->and(GetMailboxAccessToken::forMailbox(Mailbox::fromEloquentModel($mailbox)))->toBe('access-token');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token'));
});

it('connects a shared mailbox through the person who signed in', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeMicrosoftSignIn();
    Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->withSession([MailboxSession::PENDING_SHARED_ADDRESS => 'support@acme.test'])
        ->get(route('mailboxes.callback'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test connected.'])
        ->assertSessionMissing(MailboxSession::PENDING_SHARED_ADDRESS);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->sole())
        ->address->toBe('support@acme.test')
        ->display_name->toBeNull()
        ->is_shared->toBeTrue();
});

it('reconnects a mailbox, keeping its settings and its place in the inbox', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $mailbox = MailboxEloquentModel::factory()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
        'address' => 'sam@acme.test',
        'group_id' => $group->id,
        'auto_create_tickets' => false,
        'last_error' => 'Connect it again.',
        'refresh_token' => 'stale-token',
        'synced_through' => '2026-11-01 09:00:00',
    ]);
    fakeMicrosoftSignIn();
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)->get(route('mailboxes.callback'))->assertRedirectToRoute('mailboxes.index');

    expect($mailbox->refresh())
        ->status->toBe('connected')
        ->last_error->toBeNull()
        ->refresh_token->toBe('refresh-token')
        ->group_id->toBe($group->id)
        ->auto_create_tickets->toBeFalse()
        ->and($mailbox->synced_through?->toDateTimeString())->toBe('2026-11-01 09:00:00')
        ->and(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('saves nothing when the inbox cannot be opened', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeMicrosoftSignIn();
    Http::fake(['graph.microsoft.com/v1.0/users/support%40acme.test/mailFolders/inbox*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403)]);

    actingAs($owner)
        ->withSession([MailboxSession::PENDING_SHARED_ADDRESS => 'support@acme.test'])
        ->get(route('mailboxes.callback'))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "We couldn't open the inbox of support@acme.test. Check the address, and that the account you signed in with has access to it.",
        ]);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('saves nothing when Microsoft gives no lasting access', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    fakeMicrosoftSignIn(['refreshToken' => null]);

    actingAs($owner)
        ->get(route('mailboxes.callback'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "Microsoft didn't give MomCRM lasting access to the mailbox. Try connecting it again.",
        ]);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('saves nothing when the sign-in is declined', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    Socialite::fake('microsoft', fn () => throw new RuntimeException('access_denied'));

    actingAs($owner)
        ->get(route('mailboxes.callback'))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "We couldn't sign in to Microsoft. Try connecting the mailbox again.",
        ]);

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('refuses a mailbox another workspace has connected', function (): void {
    Http::preventStrayRequests();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = MailboxEloquentModel::factory()->create(['address' => 'sam@acme.test', 'refresh_token' => 'their-token']);
    fakeMicrosoftSignIn();
    Http::fake(['graph.microsoft.com/v1.0/me/mailFolders/inbox*' => Http::response(['id' => 'inbox-id'])]);

    actingAs($owner)
        ->get(route('mailboxes.callback'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'sam@acme.test is already connected to another workspace.']);

    expect($theirs->refresh())
        ->team_id->not->toBe($owner->current_team_id)
        ->refresh_token->toBe('their-token')
        ->and(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    fakeMicrosoftSignIn();

    actingAs($member)->get(route('mailboxes.callback'))->assertForbidden();

    expect(MailboxEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
