<?php

declare(strict_types=1);

use App\Shift\ShiftEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('exports a row per member per day of the period, in hours', function (): void {
    travelTo('2026-11-25 12:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada', 'email' => 'ada@example.test', 'timezone' => 'UTC']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['name' => '=HYPERLINK("x")', 'timezone' => 'UTC']);
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->weekdays('09:00', '17:00', 30)->create();
    ShiftEloquentModel::factory()->forUser($owner)->between(CarbonImmutable::parse('2026-11-16 09:00'), 9)->create();

    $response = actingAs($owner)->get(route('attendance.timesheets.export', ['period' => '2026-11-16']));

    $response->assertOk()->assertDownload('timesheets-2026-11-16.csv');
    $rows = array_map(str_getcsv(...), array_filter(explode("\n", $response->streamedContent())));

    expect($rows)->toHaveCount(1 + 2 * 7)
        ->and($rows[0])->toBe(['Member', 'Email', 'Date', 'Status', 'Flags', 'Leave', 'Expected hours', 'Worked hours', 'Break hours', 'Overtime hours', 'Timesheet'])
        ->and($rows[1][0])->toBe('\'=HYPERLINK("x")')
        ->and($rows[1][3])->toBe('absent')
        ->and($rows[8])->toBe(['Ada', 'ada@example.test', '2026-11-16', 'present', 'overtime', '', '7.50', '9.00', '0.00', '1.50', 'open']);
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->get(route('attendance.timesheets.export'))->assertForbidden();
});
