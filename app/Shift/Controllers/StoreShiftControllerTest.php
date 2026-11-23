<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
});

it('adds a forgotten shift with its breaks, read in the member’s timezone', function (): void {
    [$owner, $member] = ownerAndPerthMember();

    actingAs($owner)->post(route('shifts.store'), [
        'user_id' => $member->id,
        'clocked_in_at' => '2026-11-20T09:00',
        'clocked_out_at' => '2026-11-20T17:30',
        'note' => 'Forgot to clock in',
        'breaks' => [['started_at' => '2026-11-20T12:00', 'ended_at' => '2026-11-20T12:30']],
    ])->assertSessionHasNoErrors();

    $shift = ShiftEloquentModel::query()->sole();

    expect($shift)
        ->user_id->toBe($member->id)
        ->team_id->toBe($owner->current_team_id)
        ->work_date->toBe('2026-11-20')
        ->clocked_in_at->toDateTimeString()->toBe('2026-11-20 01:00:00')
        ->clocked_out_at->toDateTimeString()->toBe('2026-11-20 09:30:00')
        ->note->toBe('Forgot to clock in')
        ->and(ShiftBreakEloquentModel::query()->sole())
        ->shift_id->toBe($shift->id)
        ->started_at->toDateTimeString()->toBe('2026-11-20 04:00:00')
        ->ended_at->toDateTimeString()->toBe('2026-11-20 04:30:00')
        ->and(AuditLogEloquentModel::query()->where('auditable_id', $shift->id)->where('event', 'created')->where('user_id', $owner->id)->exists())->toBeTrue();
});

it('refuses members adding shifts for someone else', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('shifts.store'), [
        'user_id' => $owner->id,
        'clocked_in_at' => '2026-11-20T09:00',
        'clocked_out_at' => '2026-11-20T17:00',
    ])->assertForbidden();

    expect(ShiftEloquentModel::query()->count())->toBe(0);
});

it('lets members add a shift they forgot while its timesheet is open', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-09')->create();

    actingAs($member)->post(route('shifts.store'), [
        'user_id' => $member->id,
        'clocked_in_at' => '2026-11-20T09:00',
        'clocked_out_at' => '2026-11-20T17:00',
    ])->assertSessionHasNoErrors();

    actingAs($member)->post(route('shifts.store'), [
        'user_id' => $member->id,
        'clocked_in_at' => '2026-11-10T09:00',
        'clocked_out_at' => '2026-11-10T17:00',
    ])->assertSessionHasErrors(['clocked_in_at' => 'Your timesheet for this day has been submitted. Ask an owner or admin to change it.']);

    expect(ShiftEloquentModel::query()->pluck('work_date')->map(fn ($date): string => substr((string) $date, 0, 10))->all())->toBe(['2026-11-20']);
});

it('keeps days in an approved timesheet from changing, but not a submitted one', function (): void {
    [$owner, $member] = ownerAndPerthMember();
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-09')->approved()->create();
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->create();
    $form = fn (string $day): array => ['user_id' => $member->id, 'clocked_in_at' => "{$day}T09:00", 'clocked_out_at' => "{$day}T17:00"];

    actingAs($owner)->post(route('shifts.store'), $form('2026-11-10'))->assertSessionHasErrors(['clocked_in_at' => 'This day is in an approved timesheet, so its time can’t change.']);
    actingAs($owner)->post(route('shifts.store'), $form('2026-11-17'))->assertSessionHasNoErrors();

    expect(ShiftEloquentModel::query()->count())->toBe(1);
});

it('only adds shifts for people in the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('shifts.store'), [
        'user_id' => $outsider->id,
        'clocked_in_at' => '2026-11-20T09:00',
        'clocked_out_at' => '2026-11-20T17:00',
    ])->assertSessionHasErrors('user_id');

    expect(ShiftEloquentModel::query()->count())->toBe(0);
});

it('requires the member and a clock-in', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('shifts.store'), [])->assertSessionHasErrors(['user_id', 'clocked_in_at']);
});

it('rejects times that do not make sense', function (array $times, string $field, string $message): void {
    [$owner, $member] = ownerAndPerthMember();

    actingAs($owner)
        ->post(route('shifts.store'), ['user_id' => $member->id, ...$times])
        ->assertSessionHasErrors([$field => $message]);

    expect(ShiftEloquentModel::query()->count())->toBe(0);
})->with([
    'clock-out before clock-in' => [
        ['clocked_in_at' => '2026-11-20T17:00', 'clocked_out_at' => '2026-11-20T09:00'],
        'clocked_out_at', 'The clock-out must be after the clock-in.',
    ],
    'in the future' => [
        ['clocked_in_at' => '2026-11-24T09:00', 'clocked_out_at' => '2026-11-24T17:00'],
        'clocked_in_at', 'The clock-in can’t be in the future.',
    ],
    'longer than a day' => [
        ['clocked_in_at' => '2026-11-18T09:00', 'clocked_out_at' => '2026-11-19T10:00'],
        'clocked_out_at', 'A shift can’t be longer than 24 hours.',
    ],
    'break before the shift' => [
        ['clocked_in_at' => '2026-11-20T09:00', 'clocked_out_at' => '2026-11-20T17:00', 'breaks' => [['started_at' => '2026-11-20T08:00', 'ended_at' => '2026-11-20T08:30']]],
        'breaks.0.started_at', 'A break must start during the shift.',
    ],
    'break past the clock-out' => [
        ['clocked_in_at' => '2026-11-20T09:00', 'clocked_out_at' => '2026-11-20T17:00', 'breaks' => [['started_at' => '2026-11-20T16:45', 'ended_at' => '2026-11-20T17:15']]],
        'breaks.0.ended_at', 'A break must end by the clock-out.',
    ],
    'overlapping breaks' => [
        ['clocked_in_at' => '2026-11-20T09:00', 'clocked_out_at' => '2026-11-20T17:00', 'breaks' => [
            ['started_at' => '2026-11-20T12:00', 'ended_at' => '2026-11-20T12:30'],
            ['started_at' => '2026-11-20T12:15', 'ended_at' => '2026-11-20T12:45'],
        ]],
        'breaks.1.started_at', 'Breaks can’t overlap.',
    ],
    'open break in a finished shift' => [
        ['clocked_in_at' => '2026-11-20T09:00', 'clocked_out_at' => '2026-11-20T17:00', 'breaks' => [['started_at' => '2026-11-20T12:00', 'ended_at' => null]]],
        'breaks.0.ended_at', 'Give the break an end, since the shift has finished.',
    ],
]);

it('refuses a shift overlapping another of the member’s shifts', function (): void {
    [$owner, $member] = ownerAndPerthMember();
    ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-20 01:00'), 8)->create();

    actingAs($owner)->post(route('shifts.store'), [
        'user_id' => $member->id,
        'clocked_in_at' => '2026-11-20T16:00',
        'clocked_out_at' => '2026-11-20T20:00',
    ])->assertSessionHasErrors(['clocked_in_at' => 'This overlaps another of their shifts.']);

    expect(ShiftEloquentModel::query()->count())->toBe(1);
});

it('refuses a second running shift', function (): void {
    [$owner, $member] = ownerAndPerthMember();
    ShiftEloquentModel::factory()->forUser($member)->open()->create();

    actingAs($owner)->post(route('shifts.store'), [
        'user_id' => $member->id,
        'clocked_in_at' => '2026-11-20T09:00',
    ])->assertSessionHasErrors(['clocked_in_at' => 'They already have a shift running at this time.']);
});
