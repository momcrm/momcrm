<?php

declare(strict_types=1);

use App\Holiday\HolidayEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('shows the team’s schedules, who works to which, and its holidays from last year on', function (): void {
    travelTo('2026-11-23 12:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => 'Ben']);
    $office = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->create();
    $nights = WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->weekdays('22:00', '06:00')->create(['name' => 'Nights']);
    WorkScheduleEloquentModel::factory()->create(['name' => 'Someone else’s']);
    TeamUserEloquentModel::query()->where('user_id', $member->id)->update(['work_schedule_id' => $nights->id]);
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2024-12-25')->create();
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2025-01-01')->create(['name' => 'New Year’s Day']);
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2026-12-25')->create(['name' => 'Christmas Day']);

    actingAs($owner)
        ->get(route('attendance-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/attendance')
            ->has('schedules', 2)
            ->where('schedules.0.id', $nights->id)
            ->where('schedules.0.days.mon', ['starts_at' => '22:00', 'ends_at' => '06:00', 'break_minutes' => 0])
            ->where('schedules.1.id', $office->id)
            ->where('schedules.1.is_default', true)
            ->where('members', [
                ['id' => $owner->id, 'name' => 'Ada', 'work_schedule_id' => null],
                ['id' => $member->id, 'name' => 'Ben', 'work_schedule_id' => $nights->id],
            ])
            ->has('holidays', 2)
            ->where('holidays.0.name', 'New Year’s Day')
            ->where('holidays.1', fn ($holiday): bool => $holiday['date'] === '2026-12-25' && $holiday['name'] === 'Christmas Day'));
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->get(route('attendance-settings.edit'))->assertForbidden();
});
