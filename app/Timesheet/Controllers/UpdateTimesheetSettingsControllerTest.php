<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('switches the team to fortnights counted from this week', function (): void {
    travelTo('2026-11-25 12:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->put(route('timesheets.settings'), ['timesheet_period' => 'fortnightly'])->assertSessionHasNoErrors();

    $team = TeamEloquentModel::query()->findOrFail($owner->current_team_id);

    expect($team->timesheet_period)->toBe('fortnightly')
        ->and($team->timesheet_starts_on)->toStartWith('2026-11-23');

    actingAs($owner)->put(route('timesheets.settings'), ['timesheet_period' => 'weekly'])->assertSessionHasNoErrors();

    expect($team->refresh()->timesheet_period)->toBe('weekly');
});

it('rejects a period it doesn’t know', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->put(route('timesheets.settings'), ['timesheet_period' => 'monthly'])->assertSessionHasErrors('timesheet_period');
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->put(route('timesheets.settings'), ['timesheet_period' => 'fortnightly'])->assertForbidden();

    expect(TeamEloquentModel::query()->findOrFail($owner->current_team_id)->timesheet_period)->toBe('weekly');
});
