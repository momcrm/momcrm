<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    travelTo('2026-11-23 12:00:00');
});

it('corrects a shift’s times and replaces its breaks, keeping the old times in the audit log', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['timezone' => 'Australia/Perth']);
    $shift = ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-20 01:00'), 8)->create();
    $oldBreak = ShiftBreakEloquentModel::factory()->forShift($shift)->between(CarbonImmutable::parse('2026-11-20 04:00'), 30)->create();

    actingAs($owner)->put(route('shifts.update', $shift->id), [
        'clocked_in_at' => '2026-11-20T08:30',
        'clocked_out_at' => '2026-11-20T17:00',
        'note' => 'Came in early',
        'breaks' => [['started_at' => '2026-11-20T13:00', 'ended_at' => '2026-11-20T13:45']],
    ])->assertSessionHasNoErrors();

    expect($shift->refresh())
        ->clocked_in_at->toDateTimeString()->toBe('2026-11-20 00:30:00')
        ->clocked_out_at->toDateTimeString()->toBe('2026-11-20 09:00:00')
        ->note->toBe('Came in early')
        ->and(ShiftBreakEloquentModel::query()->find($oldBreak->id))->toBeNull()
        ->and(ShiftBreakEloquentModel::query()->sole())
        ->started_at->toDateTimeString()->toBe('2026-11-20 05:00:00')
        ->ended_at->toDateTimeString()->toBe('2026-11-20 05:45:00');

    $audit = AuditLogEloquentModel::query()->where('auditable_id', $shift->id)->where('event', 'updated')->sole();

    expect($audit->user_id)->toBe($owner->id)
        ->and($audit->old_values['clocked_in_at'] ?? null)->toStartWith('2026-11-20 01:00:00');
});

it('dates the shift by its new clock-in', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    $shift = ShiftEloquentModel::factory()->forUser($owner)->between(CarbonImmutable::parse('2026-11-20 09:00'), 8)->create();

    actingAs($owner)->put(route('shifts.update', $shift->id), [
        'clocked_in_at' => '2026-11-19T09:00',
        'clocked_out_at' => '2026-11-19T17:00',
    ])->assertSessionHasNoErrors();

    expect($shift->refresh()->work_date)->toBe('2026-11-19');
});

it('refuses members, even for their own shift', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $shift = ShiftEloquentModel::factory()->forUser($member)->create();

    actingAs($member)->put(route('shifts.update', $shift->id), [
        'clocked_in_at' => '2026-11-20T08:00',
        'clocked_out_at' => '2026-11-20T17:00',
    ])->assertForbidden();
});

it('does not find another team’s shift', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = ShiftEloquentModel::factory()->create();

    actingAs($owner)->put(route('shifts.update', $theirs->id), [
        'clocked_in_at' => '2026-11-20T08:00',
        'clocked_out_at' => '2026-11-20T17:00',
    ])->assertNotFound();
});
