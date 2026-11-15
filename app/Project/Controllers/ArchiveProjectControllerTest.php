<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves a project to the archive', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Refit']);

    actingAs($user)
        ->post(route('projects.archive', $project->id))
        ->assertRedirectToRoute('projects.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Refit moved to the archive.']);

    expect($project->fresh()->archived_at)->not->toBeNull();
});

it("returns not found for another team's project", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('projects.archive', ProjectEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('leaves archiving to owners and admins by default', function (TeamRoleEnum $role, bool $allowed): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $person = UserEloquentModel::factory()->inTeam($owner->current_team_id, $role)->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    $response = actingAs($person)->post(route('projects.archive', $project->id));

    $allowed ? $response->assertRedirectToRoute('projects.index') : $response->assertForbidden();
    expect($project->fresh()->archived_at !== null)->toBe($allowed);
})->with([
    'member' => [TeamRoleEnum::Member, false],
    'admin' => [TeamRoleEnum::Admin, true],
]);
