<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames and recolours a status but keeps its category', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $closed = ticketStatusId($owner->current_team_id, 'Closed');

    actingAs($owner)
        ->patch(route('choices.update', $closed), ['name' => 'Done', 'color' => 'teal', 'category' => 'open'])
        ->assertSessionHasNoErrors();

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->find($closed))
        ->name->toBe('Done')
        ->color->toBe('teal')
        ->category->toBe('closed');
});

it('makes a status the default of its category only', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;

    actingAs($owner)
        ->patch(route('choices.update', ticketStatusId($teamId, 'In progress')), ['is_default' => true])
        ->assertSessionHasNoErrors();

    expect(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Open->value)->getName()->toString())->toBe('In progress')
        ->and(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Closed->value)->getName()->toString())->toBe('Closed')
        ->and(ChoiceEloquentModel::query()->withoutGlobalScopes()->where('team_id', $teamId)->where('list', 'ticket_status')->where('is_default', true)->count())->toBe(2);
});

it("rejects a name already in the list, and returns not found for another team's choice", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->patch(route('choices.update', ticketStatusId($owner->current_team_id, 'New')), ['name' => 'Closed'])
        ->assertSessionHasErrors(['name' => 'That name is already in this list, or in its archive.']);

    actingAs($owner)
        ->patch(route('choices.update', ticketStatusId(UserEloquentModel::factory()->withPersonalTeam()->create()->current_team_id, 'New')), ['name' => 'Mine'])
        ->assertNotFound();
});

it('does not make an archived choice the default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $inProgress = ticketStatusId($owner->current_team_id, 'In progress');
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($inProgress)->update(['archived_at' => now()]);

    actingAs($owner)
        ->patch(route('choices.update', $inProgress), ['is_default' => true])
        ->assertSessionHasErrors(['is_default' => 'Restore In progress before making it the default.']);
});
