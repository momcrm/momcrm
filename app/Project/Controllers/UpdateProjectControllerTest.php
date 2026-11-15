<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('changes just the status', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Refit', 'status' => 'planning']);

    actingAs($user)
        ->from(route('projects.show', $project->id))
        ->put(route('projects.update', $project->id), ['status' => 'at_risk'])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('projects.show', $project->id);

    expect($project->fresh())->status->toBe('at_risk')->name->toBe('Refit');
});

it('replaces the members, keeping the lead', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $colleague = UserEloquentModel::factory()->create();
    TeamUserEloquentModel::query()->create(['team_id' => $user->current_team_id, 'user_id' => $colleague->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'lead_id' => $user->id]);

    actingAs($user)->put(route('projects.update', $project->id), ['member_ids' => [$colleague->id]])->assertSessionHasNoErrors();

    expect($project->fresh()->members->pluck('id')->sort()->values()->all())->toBe(collect([$user->id, $colleague->id])->sort()->values()->all());
});

it("returns not found for another team's project", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('projects.update', ProjectEloquentModel::factory()->create()->id), ['status' => 'at_risk'])
        ->assertNotFound();
});

it('removes every member but the lead when none are picked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $colleague = UserEloquentModel::factory()->create();
    TeamUserEloquentModel::query()->create(['team_id' => $user->current_team_id, 'user_id' => $colleague->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'lead_id' => $user->id]);
    $project->members()->attach([$user->id, $colleague->id]);

    actingAs($user)->put(route('projects.update', $project->id), ['members_sent' => '1'])->assertSessionHasNoErrors();

    expect($project->fresh()->members->pluck('id')->all())->toBe([$user->id]);
});

it('keeps the members when the form did not include them', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $colleague = UserEloquentModel::factory()->create();
    TeamUserEloquentModel::query()->create(['team_id' => $user->current_team_id, 'user_id' => $colleague->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'lead_id' => $user->id]);
    $project->members()->attach([$user->id, $colleague->id]);

    actingAs($user)->put(route('projects.update', $project->id), ['status' => 'at_risk']);

    expect($project->fresh()->members)->toHaveCount(2);
});

it("lets a member edit a project but not change who can see it or who's in it", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'visibility' => 'open', 'lead_id' => $owner->id, 'status' => 'planning']);
    $project->members()->attach($owner->id);

    actingAs($member)->put(route('projects.update', $project->id), ['status' => 'at_risk', 'member_ids' => [$owner->id]])->assertSessionHasNoErrors();
    actingAs($member)->put(route('projects.update', $project->id), ['visibility' => 'restricted'])->assertForbidden();
    actingAs($member)->put(route('projects.update', $project->id), ['member_ids' => [$owner->id, $member->id]])->assertForbidden();

    expect($project->fresh())->status->toBe('at_risk')->visibility->toBe('open')
        ->and($project->fresh()->members->pluck('id')->all())->toBe([$owner->id]);
});
