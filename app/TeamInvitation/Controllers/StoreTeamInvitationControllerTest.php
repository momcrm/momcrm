<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\TeamInvitation\Notifications\TeamInvitationNotification;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Notification::fake();
});

it('invites each address and emails them a link to join', function (): void {
    travelTo('2026-11-14 10:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);

    $response = actingAs($owner)->post(route('team.invitations.store'), [
        'emails' => 'sam@acme.test, Lee@Acme.test',
        'role' => 'admin',
    ]);

    $response->assertRedirectToRoute('team.edit');
    expect(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->orderBy('email')->get(['email', 'role', 'team_id', 'expires_at'])->toArray())
        ->toBe([
            ['email' => 'lee@acme.test', 'role' => 'admin', 'team_id' => $owner->current_team_id, 'expires_at' => '2026-11-21T10:00:00.000000Z'],
            ['email' => 'sam@acme.test', 'role' => 'admin', 'team_id' => $owner->current_team_id, 'expires_at' => '2026-11-21T10:00:00.000000Z'],
        ]);
    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        fn (TeamInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'sam@acme.test'
            && $notification->toMail($notifiable)->subject === "Jane Smith invited you to Jane Smith's Team on MomCRM",
    );
    Notification::assertSentOnDemandTimes(TeamInvitationNotification::class, 2);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::InvitationSent->value)->count())->toBe(2);
});

it('skips addresses that already belong to the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create(['email' => 'member@acme.test']);
    CreateTeamUser::fromIds(teamId: $owner->current_team_id, userId: $member->id);

    actingAs($owner)
        ->post(route('team.invitations.store'), ['emails' => 'member@acme.test'])
        ->assertRedirectToRoute('team.edit');

    expect(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('sends a fresh link when an address is invited again', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    actingAs($owner)->post(route('team.invitations.store'), ['emails' => 'sam@acme.test']);
    $firstHash = TeamInvitationEloquentModel::query()->withoutGlobalScopes()->sole()->token_hash;

    actingAs($owner)->post(route('team.invitations.store'), ['emails' => 'sam@acme.test']);

    expect(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->sole()->token_hash)->not->toBe($firstHash);
});

it('rejects an address that is not an email', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('team.invitations.store'), ['emails' => 'sam@acme.test, not-an-email'])
        ->assertSessionHasErrors(['email_list.1' => 'not-an-email is not a valid email address.']);

    expect(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('requires at least one address', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('team.invitations.store'), ['emails' => ''])
        ->assertSessionHasErrors(['emails' => 'Add at least one email address.']);
});

it('forbids members who are not owners or admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $owner->current_team_id, userId: $member->id, role: TeamRoleEnum::Member);
    $member->forceFill(['current_team_id' => $owner->current_team_id])->save();

    actingAs($member)
        ->post(route('team.invitations.store'), ['emails' => 'sam@acme.test'])
        ->assertForbidden();

    Notification::assertNothingSent();
});
