<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\Notifications\LeaveRequestedNotification;
use App\LeaveRequest\Notifications\LeaveReviewedNotification;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
    Notification::fake();
});

/**
 * The id of a leave type in the team, by name.
 */
function leaveTypeId(string $teamId, string $name = 'Annual leave'): string
{
    return ChoiceEloquentModel::query()->withoutGlobalScopes()->where('team_id', $teamId)->where('list', 'leave_type')->where('name', $name)->value('id');
}

it('asks the owners and admins for a member’s leave', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $other = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('leave-requests.store'), [
        'leave_type_id' => leaveTypeId($owner->current_team_id),
        'starts_on' => '2026-12-21',
        'ends_on' => '2026-12-24',
        'part' => 'full',
        'reason' => 'Family visit',
    ])->assertSessionHasNoErrors();

    $leave = LeaveRequestEloquentModel::query()->sole();

    expect($leave->user_id)->toBe($member->id)
        ->and($leave->status)->toBe('pending')
        ->and($leave->starts_on)->toStartWith('2026-12-21')
        ->and($leave->reason)->toBe('Family visit')
        ->and($leave->reviewed_by)->toBeNull();

    Notification::assertSentTo([$owner, $admin], LeaveRequestedNotification::class, fn (LeaveRequestedNotification $notification): bool => $notification->leave->is($leave) && $notification->days === 'Mon 21 Dec – Thu 24 Dec');
    Notification::assertNotSentTo([$member, $other], LeaveRequestedNotification::class);
});

it('lets owners record leave for someone, approved straight away', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($owner)->post(route('leave-requests.store'), [
        'user_id' => $member->id,
        'leave_type_id' => leaveTypeId($owner->current_team_id, 'Sick leave'),
        'starts_on' => '2026-11-23',
        'ends_on' => '2026-11-23',
        'part' => 'full',
    ])->assertSessionHasNoErrors();

    $leave = LeaveRequestEloquentModel::query()->sole();

    expect($leave->user_id)->toBe($member->id)
        ->and($leave->status)->toBe('approved')
        ->and($leave->reviewed_by)->toBe($owner->id);

    Notification::assertSentTo($member, LeaveReviewedNotification::class);
    Notification::assertNotSentTo($owner, LeaveRequestedNotification::class);
});

it('refuses members recording leave for someone else', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('leave-requests.store'), [
        'user_id' => $owner->id,
        'leave_type_id' => leaveTypeId($owner->current_team_id),
        'starts_on' => '2026-12-01',
        'ends_on' => '2026-12-01',
        'part' => 'full',
    ])->assertForbidden();

    expect(LeaveRequestEloquentModel::query()->count())->toBe(0);
});

it('rejects leave that doesn’t make sense', function (array $form, string $field): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    LeaveRequestEloquentModel::factory()->forUser($user)->between('2026-12-07', '2026-12-09')->create();
    ChoiceEloquentModel::query()->whereKey(leaveTypeId($user->current_team_id, 'Unpaid leave'))->update(['archived_at' => now()]);
    $theirs = UserEloquentModel::factory()->withPersonalTeam()->create();
    $typeIds = ['own' => leaveTypeId($user->current_team_id), 'archived' => leaveTypeId($user->current_team_id, 'Unpaid leave'), 'theirs' => leaveTypeId($theirs->current_team_id)];

    actingAs($user)->post(route('leave-requests.store'), [
        'starts_on' => '2026-12-01',
        'ends_on' => '2026-12-01',
        'part' => 'full',
        ...$form,
        'leave_type_id' => $typeIds[$form['leave_type_id'] ?? 'own'],
    ])->assertSessionHasErrors($field);

    expect(LeaveRequestEloquentModel::query()->count())->toBe(1);
})->with([
    'ending before it starts' => [['ends_on' => '2026-11-30'], 'ends_on'],
    'half of several days' => [['ends_on' => '2026-12-02', 'part' => 'morning'], 'part'],
    'longer than a year' => [['ends_on' => '2027-12-02'], 'ends_on'],
    'an unknown part' => [['part' => 'evening'], 'part'],
    'an archived type' => [['leave_type_id' => 'archived'], 'leave_type_id'],
    'another team’s type' => [['leave_type_id' => 'theirs'], 'leave_type_id'],
    'overlapping leave already asked for' => [['starts_on' => '2026-12-09', 'ends_on' => '2026-12-10'], 'starts_on'],
]);

it('allows a morning and an afternoon off on the same day', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $form = ['leave_type_id' => leaveTypeId($user->current_team_id), 'starts_on' => '2026-12-01', 'ends_on' => '2026-12-01'];

    actingAs($user)->post(route('leave-requests.store'), [...$form, 'part' => 'morning'])->assertSessionHasNoErrors();
    actingAs($user)->post(route('leave-requests.store'), [...$form, 'part' => 'afternoon'])->assertSessionHasNoErrors();
    actingAs($user)->post(route('leave-requests.store'), [...$form, 'part' => 'morning'])->assertSessionHasErrors('starts_on');

    expect(LeaveRequestEloquentModel::query()->count())->toBe(2);
});
