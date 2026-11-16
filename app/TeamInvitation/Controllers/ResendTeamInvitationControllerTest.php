<?php

declare(strict_types=1);

use App\TeamInvitation\Notifications\TeamInvitationNotification;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Notification::fake();
});

it('emails a pending invitation again with a new link and a fresh 7 days, keeping its role', function (): void {
    travelTo('2026-11-16 10:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invitation = TeamInvitationEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'email' => 'sam@acme.test',
        'role' => 'admin',
        'token_hash' => 'old-hash',
        'expires_at' => '2026-11-17 10:00:00',
    ]);

    actingAs($owner)
        ->from(route('members'))
        ->post(route('team.invitations.resend', $invitation->id))
        ->assertRedirectToRoute('members')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Invite resent to sam@acme.test.']);

    $invitation->refresh();
    expect($invitation->role)->toBe('admin')
        ->and($invitation->token_hash)->not->toBe('old-hash')
        ->and($invitation->expires_at->toDateTimeString())->toBe('2026-11-23 10:00:00')
        ->and(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
    Notification::assertSentOnDemand(
        TeamInvitationNotification::class,
        fn (TeamInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'sam@acme.test',
    );
});

it('does not let a member resend invitations', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create(['current_team_id' => $owner->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $owner->current_team_id, 'user_id' => $member->id, 'role' => 'member']);
    $invitation = TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->post(route('team.invitations.resend', $invitation->id))
        ->assertForbidden();

    Notification::assertNothingSent();
});

it("returns not found for another team's invitation or one already accepted", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $accepted = TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'accepted_at' => now()]);

    actingAs($owner)
        ->post(route('team.invitations.resend', TeamInvitationEloquentModel::factory()->create()->id))
        ->assertNotFound();
    actingAs($owner)
        ->post(route('team.invitations.resend', $accepted->id))
        ->assertNotFound();

    Notification::assertNothingSent();
});
