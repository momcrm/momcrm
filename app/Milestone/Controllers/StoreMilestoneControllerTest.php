<?php

declare(strict_types=1);

use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a milestone to a project', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('milestones.store', $project->id), ['name' => 'Go live', 'due_on' => '2026-12-20'])->assertSessionHasNoErrors();

    expect(MilestoneEloquentModel::query()->sole())
        ->name->toBe('Go live')
        ->project_id->toBe($project->id)
        ->team_id->toBe($user->current_team_id);
});

it('requires a name and date', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('milestones.store', $project->id), [])->assertSessionHasErrors(['name', 'due_on']);
});

it("returns not found for another team's project", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('milestones.store', ProjectEloquentModel::factory()->create()->id), ['name' => 'X', 'due_on' => '2026-12-20'])
        ->assertNotFound();
});
