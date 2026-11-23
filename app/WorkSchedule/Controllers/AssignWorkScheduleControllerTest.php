<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

use function Pest\Laravel\actingAs;

it('puts a member on a schedule, and back on the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $nights = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();
    $membership = fn (): TeamUserEloquentModel => TeamUserEloquentModel::query()->where('user_id', $member->id)->sole();

    actingAs($owner)->put(route('work-schedules.assign', $member->id), ['work_schedule_id' => $nights->id])->assertSessionHasNoErrors();

    expect($membership()->work_schedule_id)->toBe($nights->id);

    actingAs($owner)->put(route('work-schedules.assign', $member->id), ['work_schedule_id' => null])->assertSessionHasNoErrors();

    expect($membership()->work_schedule_id)->toBeNull();
});

it('rejects another team’s schedule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $theirs = WorkScheduleEloquentModel::factory()->create();

    actingAs($owner)->put(route('work-schedules.assign', $member->id), ['work_schedule_id' => $theirs->id])->assertSessionHasErrors('work_schedule_id');

    expect(TeamUserEloquentModel::query()->where('user_id', $member->id)->sole()->work_schedule_id)->toBeNull();
});

it('does not find someone outside the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $stranger = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->put(route('work-schedules.assign', $stranger->id), ['work_schedule_id' => null])->assertNotFound();
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $nights = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($member)->put(route('work-schedules.assign', $member->id), ['work_schedule_id' => $nights->id])->assertForbidden();

    expect(TeamUserEloquentModel::query()->where('user_id', $member->id)->sole()->work_schedule_id)->toBeNull();
});
