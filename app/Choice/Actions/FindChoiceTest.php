<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Team\TeamEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;

it("lists one list of a team's choices in order, leaving out archived ones unless asked", function (): void {
    $team = TeamEloquentModel::factory()->create();
    ChoiceEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->id)->delete();
    ChoiceEloquentModel::factory()->create(['team_id' => $team->id, 'name' => 'Second', 'position' => 1]);
    ChoiceEloquentModel::factory()->create(['team_id' => $team->id, 'name' => 'First', 'position' => 0]);
    ChoiceEloquentModel::factory()->create(['team_id' => $team->id, 'name' => 'Retired', 'position' => 2, 'archived_at' => now()]);
    ChoiceEloquentModel::factory()->ticketPriority()->create(['team_id' => $team->id, 'name' => 'A priority']);
    ChoiceEloquentModel::factory()->create(['name' => 'Another team']);
    $names = fn (bool $withArchived): array => FindChoice::listForTeam($team->id, ChoiceListEnum::TicketStatus, $withArchived)
        ->map(fn (Choice $choice): string => $choice->getName()->toString())
        ->all();

    expect($names(false))->toBe(['First', 'Second'])
        ->and($names(true))->toBe(['First', 'Second', 'Retired']);
});

it('finds the default of a list, or of one category in it', function (): void {
    $teamId = TeamEloquentModel::factory()->create()->id;

    expect(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Open->value)->getName()->toString())->toBe('New')
        ->and(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Closed->value)->getName()->toString())->toBe('Closed')
        ->and(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketPriority)->getName()->toString())->toBe('Normal');
});

it('finds no default when the list has none', function (): void {
    $teamId = TeamEloquentModel::factory()->create()->id;
    ChoiceEloquentModel::query()->withoutGlobalScopes()->where('team_id', $teamId)->update(['is_default' => false]);

    expect(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketPriority)->isNull())->toBeTrue();
});
