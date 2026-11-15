<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the owner the team's lists with how many tickets use each choice", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    TicketEloquentModel::factory()->count(2)->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->get(route('workflow.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/workflow')
            ->where('lists.0.value', 'ticket_priority')
            ->where('lists.0.icons.0', 'chevrons-up')
            ->where('lists.0.categories', [])
            ->where('lists.1.value', 'ticket_status')
            ->where('lists.1.categories', [['value' => 'closed', 'label' => 'Closed'], ['value' => 'open', 'label' => 'Open']])
            ->where('lists.1.choices.0.name', 'New')
            ->where('lists.1.choices.0.usage', 2)
            ->where('lists.1.choices.4.usage', 0),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('workflow.edit'))->assertForbidden();
});
