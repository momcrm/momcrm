<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes a schedule, moving its members to the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $nights = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create(['name' => 'Nights']);
    $membership = TeamUserEloquentModel::query()->where('user_id', $member->id)->sole();
    $membership->update(['work_schedule_id' => $nights->id]);

    actingAs($owner)->delete(route('work-schedules.destroy', $nights->id))->assertSessionHasNoErrors();

    expect(WorkScheduleEloquentModel::query()->whereKey($nights->id)->exists())->toBeFalse()
        ->and($membership->refresh()->work_schedule_id)->toBeNull();
});

it('keeps the default while there are other schedules, but lets the last one go', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $default = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $other = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($owner)->delete(route('work-schedules.destroy', $default->id))->assertSessionHasErrors('schedule');

    expect(WorkScheduleEloquentModel::query()->whereKey($default->id)->exists())->toBeTrue();

    actingAs($owner)->delete(route('work-schedules.destroy', $other->id))->assertSessionHasNoErrors();
    actingAs($owner)->delete(route('work-schedules.destroy', $default->id))->assertSessionHasNoErrors();

    expect(WorkScheduleEloquentModel::query()->count())->toBe(0);
});

it('does not find another team’s schedule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = WorkScheduleEloquentModel::factory()->create();

    actingAs($owner)->delete(route('work-schedules.destroy', $theirs->id))->assertNotFound();

    expect(WorkScheduleEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $schedule = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($member)->delete(route('work-schedules.destroy', $schedule->id))->assertForbidden();

    expect(WorkScheduleEloquentModel::query()->count())->toBe(1);
});
