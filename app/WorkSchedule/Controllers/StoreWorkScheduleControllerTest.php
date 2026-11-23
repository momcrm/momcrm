<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a schedule, making the team’s first one its default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('work-schedules.store'), officeHoursForm())->assertSessionHasNoErrors();
    actingAs($owner)->post(route('work-schedules.store'), officeHoursForm(['name' => 'Nights', 'days' => ['mon' => ['starts_at' => '22:00', 'ends_at' => '06:00', 'break_minutes' => 0]]]))->assertSessionHasNoErrors();

    $office = WorkScheduleEloquentModel::query()->where('name', 'Office hours')->sole();
    $nights = WorkScheduleEloquentModel::query()->where('name', 'Nights')->sole();

    expect($office->team_id)->toBe($owner->current_team_id)
        ->and($office->is_default)->toBeTrue()
        ->and($office->grace_minutes)->toBe(5)
        ->and($office->days['mon'])->toEqual(['starts_at' => '09:00', 'ends_at' => '17:00', 'break_minutes' => 30])
        ->and($office->days['sat'])->toBeNull()
        ->and($nights->is_default)->toBeFalse()
        ->and($nights->days['mon'])->toEqual(['starts_at' => '22:00', 'ends_at' => '06:00', 'break_minutes' => 0])
        ->and($nights->days['tue'])->toBeNull();
});

it('rejects a schedule that doesn’t make sense', function (array $overrides, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('work-schedules.store'), officeHoursForm($overrides))->assertSessionHasErrors($field);

    expect(WorkScheduleEloquentModel::query()->count())->toBe(0);
})->with([
    'no working days' => [['days' => ['mon' => null, 'tue' => null]], 'days'],
    'a break as long as the day' => [['days' => ['mon' => ['starts_at' => '09:00', 'ends_at' => '10:00', 'break_minutes' => 60]]], 'days.mon.break_minutes'],
    'an end the same as the start' => [['days' => ['mon' => ['starts_at' => '09:00', 'ends_at' => '09:00']]], 'days.mon.ends_at'],
    'a time that isn’t one' => [['days' => ['mon' => ['starts_at' => '25:00', 'ends_at' => '17:00']]], 'days.mon.starts_at'],
    'an unknown day' => [['days' => ['someday' => ['starts_at' => '09:00', 'ends_at' => '17:00']]], 'days'],
    'too much grace' => [['grace_minutes' => 121], 'grace_minutes'],
    'no name' => [['name' => ''], 'name'],
]);

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('work-schedules.store'), officeHoursForm())->assertForbidden();

    expect(WorkScheduleEloquentModel::query()->count())->toBe(0);
});
