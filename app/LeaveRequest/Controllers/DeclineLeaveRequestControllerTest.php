<?php

declare(strict_types=1);

use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Notifications\LeaveReviewedNotification;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Notification::fake();
});

it('marks a waiting request declined with a note and tells the member', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $leave = LeaveRequestEloquentModel::factory()->forUser($member)->create();

    actingAs($owner)->post(route('leave-requests.decline', $leave->id), ['review_note' => 'Noted'])->assertSessionHasNoErrors();

    $leave->refresh();

    expect($leave->status)->toBe('declined')
        ->and($leave->reviewed_by)->toBe($owner->id)
        ->and($leave->reviewed_at)->not->toBeNull()
        ->and($leave->review_note)->toBe('Noted');

    Notification::assertSentTo($member, LeaveReviewedNotification::class, fn (LeaveReviewedNotification $notification): bool => $notification->toArray($member)['message'] === 'declined your leave');
});

it('leaves a request that was already answered alone', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $leave = LeaveRequestEloquentModel::factory()->forUser($owner)->status(LeaveStatusEnum::Cancelled)->create();

    actingAs($owner)->post(route('leave-requests.decline', $leave->id))->assertSessionHasErrors('leave');

    expect($leave->refresh()->status)->toBe('cancelled');
});

it('refuses members, even for their own leave', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $leave = LeaveRequestEloquentModel::factory()->forUser($member)->create();

    actingAs($member)->post(route('leave-requests.decline', $leave->id))->assertForbidden();

    expect($leave->refresh()->status)->toBe('pending');
});

it('does not find another team’s leave', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = LeaveRequestEloquentModel::factory()->create();

    actingAs($owner)->post(route('leave-requests.decline', $theirs->id))->assertNotFound();

    expect($theirs->refresh()->status)->toBe('pending');
});
