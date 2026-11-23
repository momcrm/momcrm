<?php

declare(strict_types=1);

use App\Holiday\HolidayEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('removes a holiday', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $holiday = HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($owner)->delete(route('holidays.destroy', $holiday->id))->assertSessionHasNoErrors();

    expect(HolidayEloquentModel::query()->count())->toBe(0);
});

it('does not find another team’s holiday', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = HolidayEloquentModel::factory()->create();

    actingAs($owner)->delete(route('holidays.destroy', $theirs->id))->assertNotFound();

    expect(HolidayEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $holiday = HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->create();

    actingAs($member)->delete(route('holidays.destroy', $holiday->id))->assertForbidden();

    expect(HolidayEloquentModel::query()->count())->toBe(1);
});
