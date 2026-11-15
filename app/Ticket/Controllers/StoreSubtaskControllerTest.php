<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a subtask with its parent's customer and project", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $parent = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id]);

    actingAs($user)
        ->from(route('tickets.index'))
        ->post(route('tickets.subtasks.store', $parent->id), ['title' => 'Order toner'])
        ->assertRedirectToRoute('tickets.index');

    expect(TicketEloquentModel::query()->where('title', 'Order toner')->sole())
        ->parent_id->toBe($parent->id)
        ->customer_id->toBe($parent->customer_id)
        ->project_id->toBe($project->id)
        ->status_id->toBe(ticketStatusId($user->current_team_id, 'New'));
});

it('requires a title', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $parent = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.subtasks.store', $parent->id), ['title' => ''])
        ->assertSessionHasErrors('title');
});

it('does not nest subtasks', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $parent = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $subtask = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'parent_id' => $parent->id]);

    actingAs($user)
        ->post(route('tickets.subtasks.store', $subtask->id), ['title' => 'Deeper'])
        ->assertNotFound();
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.subtasks.store', TicketEloquentModel::factory()->create()->id), ['title' => 'X'])
        ->assertNotFound();
});
