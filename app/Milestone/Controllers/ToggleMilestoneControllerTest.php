<?php

declare(strict_types=1);

use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('marks a milestone done and not done again', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $milestone = MilestoneEloquentModel::factory()->create(['project_id' => ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id]);

    actingAs($user)->post(route('milestones.toggle', $milestone->id));
    expect($milestone->fresh()->completed_at)->not->toBeNull();

    actingAs($user)->post(route('milestones.toggle', $milestone->id));
    expect($milestone->fresh()->completed_at)->toBeNull();
});

it("returns not found for another team's milestone", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('milestones.toggle', MilestoneEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
