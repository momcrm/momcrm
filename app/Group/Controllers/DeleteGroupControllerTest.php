<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes a group, keeping its projects', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Support']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'group_id' => $group->id]);

    actingAs($user)
        ->delete(route('groups.destroy', $group->id))
        ->assertRedirectToRoute('members', ['tab' => 'groups']);

    expect(GroupEloquentModel::query()->exists())->toBeFalse()
        ->and($project->fresh()->group_id)->toBeNull();
});

it("returns not found for another team's group", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('groups.destroy', GroupEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
