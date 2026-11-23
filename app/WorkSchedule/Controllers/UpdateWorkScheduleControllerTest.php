<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

use function Pest\Laravel\actingAs;

it('changes a schedule’s name, hours and grace', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $schedule = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();

    actingAs($owner)->put(route('work-schedules.update', $schedule->id), officeHoursForm([
        'name' => 'Early',
        'grace_minutes' => 10,
        'days' => ['mon' => ['starts_at' => '07:00', 'ends_at' => '15:00', 'break_minutes' => 0]],
    ]))->assertSessionHasNoErrors();

    $schedule->refresh();

    expect($schedule->name)->toBe('Early')
        ->and($schedule->grace_minutes)->toBe(10)
        ->and($schedule->is_default)->toBeTrue()
        ->and($schedule->days['mon'])->toEqual(['starts_at' => '07:00', 'ends_at' => '15:00', 'break_minutes' => 0])
        ->and($schedule->days['tue'])->toBeNull();
});

it('does not find another team’s schedule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = WorkScheduleEloquentModel::factory()->create();

    actingAs($owner)->put(route('work-schedules.update', $theirs->id), officeHoursForm(['name' => 'Mine now']))->assertNotFound();

    expect($theirs->refresh()->name)->toBe('Office hours');
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $schedule = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($member)->put(route('work-schedules.update', $schedule->id), officeHoursForm(['name' => 'Mine now']))->assertForbidden();

    expect($schedule->refresh()->name)->toBe('Office hours');
});
