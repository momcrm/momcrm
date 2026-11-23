<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

it('removes a shift and its breaks, keeping a record in the audit log', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $shift = ShiftEloquentModel::factory()->forUser($member)->create();
    ShiftBreakEloquentModel::factory()->forShift($shift)->create();

    actingAs($owner)->delete(route('shifts.destroy', $shift->id))->assertSessionHasNoErrors();

    expect(ShiftEloquentModel::query()->count())->toBe(0)
        ->and(ShiftBreakEloquentModel::query()->count())->toBe(0)
        ->and(AuditLogEloquentModel::query()->where('auditable_id', $shift->id)->where('event', 'deleted')->exists())->toBeTrue();
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $shift = ShiftEloquentModel::factory()->forUser($member)->create();

    actingAs($member)->delete(route('shifts.destroy', $shift->id))->assertForbidden();

    expect(ShiftEloquentModel::query()->count())->toBe(1);
});

it('does not find another team’s shift', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = ShiftEloquentModel::factory()->create();

    actingAs($owner)->delete(route('shifts.destroy', $theirs->id))->assertNotFound();

    expect(ShiftEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('keeps a shift in an approved timesheet', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $shift = ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-10 09:00'), 8)->create();
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-09')->approved()->create();

    actingAs($owner)->delete(route('shifts.destroy', $shift->id))->assertSessionHasErrors('shift');

    expect(ShiftEloquentModel::query()->count())->toBe(1);
});
