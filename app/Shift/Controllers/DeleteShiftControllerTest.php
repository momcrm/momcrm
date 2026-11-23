<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\ShiftBreakEloquentModel;
use App\User\UserEloquentModel;

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
