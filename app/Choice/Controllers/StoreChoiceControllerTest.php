<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a closed status to the bottom of the list', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('choices.store'), ['list' => 'ticket_status', 'name' => "Won't fix", 'color' => 'slate', 'category' => 'closed'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => "Won't fix added."]);

    $added = FindChoice::listForTeam($owner->current_team_id, ChoiceListEnum::TicketStatus)->last();
    expect($added)
        ->getName()->toString()->toBe("Won't fix")
        ->getCategory()->toString()->toBe('closed')
        ->getPosition()->toInteger()->toBe(5)
        ->getIsDefault()->toBoolean()->toBeFalse();
});

it('adds a priority with an icon and no category', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('choices.store'), ['list' => 'ticket_priority', 'name' => 'Someday', 'color' => 'teal', 'icon' => 'minus'])
        ->assertSessionHasNoErrors();

    expect(FindChoice::listForTeam($owner->current_team_id, ChoiceListEnum::TicketPriority)->map(fn (Choice $choice): string => $choice->getName()->toString())->all())
        ->toBe(['Urgent', 'High', 'Normal', 'Low', 'Someday']);
});

it('needs a category for statuses, refuses one for priorities, and checks the icon and name', function (array $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('choices.store'), ['color' => 'slate', ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'status without category' => [['list' => 'ticket_status', 'name' => 'Parked'], 'category'],
    'priority with category' => [['list' => 'ticket_priority', 'name' => 'Someday', 'category' => 'open'], 'category'],
    'unknown icon' => [['list' => 'ticket_priority', 'name' => 'Someday', 'icon' => 'rocket'], 'icon'],
    'taken name' => [['list' => 'ticket_status', 'name' => 'Closed', 'category' => 'closed'], 'name'],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('choices.store'), ['list' => 'ticket_status', 'name' => 'Parked', 'color' => 'slate', 'category' => 'open'])
        ->assertForbidden();
});
