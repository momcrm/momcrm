<?php

declare(strict_types=1);

use App\Holiday\HolidayEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a holiday for the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('holidays.store'), ['date' => '2026-12-25', 'name' => 'Christmas Day'])->assertSessionHasNoErrors();

    $holiday = HolidayEloquentModel::query()->sole();

    expect($holiday->team_id)->toBe($owner->current_team_id)
        ->and($holiday->date)->toStartWith('2026-12-25')
        ->and($holiday->name)->toBe('Christmas Day');
});

it('allows one holiday a day in each team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    HolidayEloquentModel::factory()->forTeam($owner->current_team_id)->on('2026-12-25')->create();
    HolidayEloquentModel::factory()->on('2026-12-26')->create();

    actingAs($owner)->post(route('holidays.store'), ['date' => '2026-12-25', 'name' => 'Again'])->assertSessionHasErrors('date');
    actingAs($owner)->post(route('holidays.store'), ['date' => '2026-12-26', 'name' => 'Boxing Day'])->assertSessionHasNoErrors();

    expect(HolidayEloquentModel::query()->count())->toBe(2);
});

it('rejects a holiday without a proper date or name', function (array $form, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('holidays.store'), $form)->assertSessionHasErrors($field);
})->with([
    'no date' => [['name' => 'Christmas Day'], 'date'],
    'not a date' => [['date' => '25/12/2026', 'name' => 'Christmas Day'], 'date'],
    'no name' => [['date' => '2026-12-25', 'name' => ''], 'name'],
]);

it('refuses members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('holidays.store'), ['date' => '2026-12-25', 'name' => 'Christmas Day'])->assertForbidden();

    expect(HolidayEloquentModel::query()->count())->toBe(0);
});
