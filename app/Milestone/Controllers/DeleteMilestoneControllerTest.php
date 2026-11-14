<?php

declare(strict_types=1);

use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes a milestone', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $milestone = MilestoneEloquentModel::factory()->create(['project_id' => ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id]);

    actingAs($user)->delete(route('milestones.destroy', $milestone->id));

    expect(MilestoneEloquentModel::query()->exists())->toBeFalse();
});

it('hides milestones of restricted projects from non-members', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $milestone = MilestoneEloquentModel::factory()->create(['project_id' => ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'visibility' => 'restricted'])->id]);

    actingAs($user)->delete(route('milestones.destroy', $milestone->id))->assertNotFound();
});
