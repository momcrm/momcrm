<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

use function Pest\Laravel\actingAs;

it('makes a schedule the team’s only default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $old = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $new = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();
    $theirs = WorkScheduleEloquentModel::factory()->teamDefault()->create();

    actingAs($owner)->post(route('work-schedules.make-default', $new->id))->assertSessionHasNoErrors();

    expect($new->refresh()->is_default)->toBeTrue()
        ->and($old->refresh()->is_default)->toBeFalse()
        ->and($theirs->refresh()->is_default)->toBeTrue();
});

it('does not find another team’s schedule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = WorkScheduleEloquentModel::factory()->create();

    actingAs($owner)->post(route('work-schedules.make-default', $theirs->id))->assertNotFound();

    expect($theirs->refresh()->is_default)->toBeFalse();
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $schedule = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($member)->post(route('work-schedules.make-default', $schedule->id))->assertForbidden();

    expect($schedule->refresh()->is_default)->toBeFalse();
});
