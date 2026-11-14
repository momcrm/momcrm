<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an archived project with its tickets', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'name' => 'Refit']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id]);
    $other = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('projects.destroy', $project->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Refit deleted.']);

    expect(ProjectEloquentModel::query()->exists())->toBeFalse()
        ->and(TicketEloquentModel::query()->pluck('id')->all())->toBe([$other->id]);
});

it('only deletes projects from the archive', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->delete(route('projects.destroy', ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id))
        ->assertNotFound();
});
