<?php

declare(strict_types=1);

use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
});

it('lets members cancel their own leave while it waits or before it starts', function (LeaveStatusEnum $leaveStatus): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    $leave = LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-11-24')->status($leaveStatus)->create();

    actingAs($member)->post(route('leave-requests.cancel', $leave->id))->assertSessionHasNoErrors();

    expect($leave->refresh()->status)->toBe('cancelled');
})->with([LeaveStatusEnum::Pending, LeaveStatusEnum::Approved]);

it('keeps approved leave that has started unless an owner cancels it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    $leave = LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-11-23', '2026-11-25')->approved()->create();

    actingAs($member)->post(route('leave-requests.cancel', $leave->id))->assertSessionHasErrors('leave');

    expect($leave->refresh()->status)->toBe('approved');

    actingAs($owner)->post(route('leave-requests.cancel', $leave->id))->assertSessionHasNoErrors();

    expect($leave->refresh()->status)->toBe('cancelled');
});

it('does nothing to leave already declined', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $leave = LeaveRequestEloquentModel::factory()->forUser($user)->status(LeaveStatusEnum::Declined)->create();

    actingAs($user)->post(route('leave-requests.cancel', $leave->id))->assertSessionHasErrors('leave');

    expect($leave->refresh()->status)->toBe('declined');
});

it('does not find someone else’s leave for a member', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $leave = LeaveRequestEloquentModel::factory()->forUser($owner)->create();

    actingAs($member)->post(route('leave-requests.cancel', $leave->id))->assertNotFound();

    expect($leave->refresh()->status)->toBe('pending');
});
