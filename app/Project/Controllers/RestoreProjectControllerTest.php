<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('brings a project back from the archive', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'name' => 'Refit']);

    actingAs($user)
        ->post(route('projects.restore', $project->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Refit restored.']);

    expect($project->fresh()->archived_at)->toBeNull();
});

it('returns not found for a project that is not archived', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('projects.restore', ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id))
        ->assertNotFound();
});
