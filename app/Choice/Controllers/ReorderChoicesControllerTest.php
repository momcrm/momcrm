<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('puts a list in a new order', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $priorities = FindChoice::listForTeam($owner->current_team_id, ChoiceListEnum::TicketPriority);

    actingAs($owner)
        ->put(route('choices.reorder'), ['list' => 'ticket_priority', 'ids' => array_reverse($priorities->ids())])
        ->assertSessionHasNoErrors();

    expect(FindChoice::listForTeam($owner->current_team_id, ChoiceListEnum::TicketPriority)->map(fn (Choice $choice): string => $choice->getName()->toString())->all())
        ->toBe(['Low', 'Normal', 'High', 'Urgent']);
});

it("rejects another team's or another list's choices", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $other = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->put(route('choices.reorder'), ['list' => 'ticket_priority', 'ids' => [ticketStatusId($owner->current_team_id, 'New')]])
        ->assertSessionHasErrors('ids.0');

    actingAs($owner)
        ->put(route('choices.reorder'), ['list' => 'ticket_status', 'ids' => [ticketStatusId($other->current_team_id, 'New')]])
        ->assertSessionHasErrors('ids.0');
});
