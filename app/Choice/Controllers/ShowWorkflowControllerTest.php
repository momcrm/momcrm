<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the owner the team's lists with how many tickets use each choice", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$ticket] = TicketEloquentModel::factory()->count(2)->create(['team_id' => $owner->current_team_id])->all();
    $ticket->labels()->attach(FindChoice::byNameForTeam($owner->current_team_id, ChoiceListEnum::Label, 'Hardware')->getId()->toString());

    actingAs($owner)
        ->get(route('workflow.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/workflow')
            ->where('current', null)
            ->where('lists.0.value', 'ticket_status')
            ->where('lists.0.categories', [['value' => 'closed', 'label' => 'Closed'], ['value' => 'open', 'label' => 'Open']])
            ->where('lists.0.choices.0.name', 'New')
            ->where('lists.0.choices.0.usage', 2)
            ->where('lists.0.choices.4.usage', 0)
            ->where('lists.1.value', 'ticket_priority')
            ->where('lists.1.icons.0', 'chevrons-up')
            ->where('lists.1.categories', [])
            ->where('lists.1.is_optional', false)
            ->where('lists.2.value', 'ticket_type')
            ->where('lists.2.is_optional', true)
            ->where('lists.2.choices.0.name', 'Incident')
            ->where('lists.3.value', 'ticket_department')
            ->where('lists.3.is_optional', true)
            ->where('lists.3.choices.0.name', 'Support')
            ->where('lists.4.value', 'label')
            ->where('lists.4.is_optional', true)
            ->where('lists.4.choices.0.name', 'Hardware')
            ->where('lists.4.choices.0.usage', 1)
            ->where('lists.4.choices.1.usage', 0),
        );
});

it('opens the list named in the query, ignoring an unknown one', function (string $list, ?string $current): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->get(route('workflow.edit', ['list' => $list]))
        ->assertInertia(fn (Assert $page) => $page->where('current', $current));
})->with([
    'a list' => ['ticket_type', 'ticket_type'],
    'an unknown list' => ['nope', null],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('workflow.edit'))->assertForbidden();
});
