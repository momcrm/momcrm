<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
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

it('sets the labels, keeping an archived one the project has but not adding one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $other = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $live = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]);
    $archived = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id, 'archived_at' => now()]);
    $project->labels()->attach($archived);

    actingAs($user)->put(route('projects.update', $project->id), ['label_ids' => [$archived->id, $live->id]])->assertSessionHasNoErrors();
    actingAs($user)->put(route('projects.update', $other->id), ['label_ids' => [$archived->id]])->assertSessionHasErrors('label_ids.0');

    expect($project->labels()->pluck('choices.id')->sort()->values()->all())->toBe(collect([$archived->id, $live->id])->sort()->values()->all())
        ->and($other->labels()->count())->toBe(0);
});

it('clears the labels when the form sends none, and keeps them when it leaves them out', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $project->labels()->attach(ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]));

    actingAs($user)->put(route('projects.update', $project->id), ['status' => 'at_risk'])->assertSessionHasNoErrors();
    expect($project->labels()->count())->toBe(1);

    actingAs($user)->put(route('projects.update', $project->id), ['labels_sent' => '1'])->assertSessionHasNoErrors();
    expect($project->labels()->count())->toBe(0);
});

it('sets project custom field values, leaving the ones not sent as they are', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Project)->create(['team_id' => $teamId]);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Project)->select(['Gold', 'Silver'])->create(['team_id' => $teamId]);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'custom_fields' => [$kept->id => 'kept']]);

    actingAs($user)
        ->put(route('projects.update', $project->id), ['custom_fields' => [$set->id => $set->options[0]['id']]])
        ->assertSessionHasNoErrors();

    expect($project->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => $set->options[0]['id']]);
});

it('rejects a project custom field value that is not valid, naming the field', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $field = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Project)->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'Tier']);
    $ticketField = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'custom_fields' => null]);

    actingAs($user)
        ->put(route('projects.update', $project->id), ['custom_fields' => [$field->id => 'nope']])
        ->assertSessionHasErrors(["custom_fields.{$field->id}" => 'The selected Tier is invalid.']);

    actingAs($user)
        ->put(route('projects.update', $project->id), ['custom_fields' => [$ticketField->id => 'SN-1']])
        ->assertSessionHasErrors('custom_fields');

    expect($project->fresh()->custom_fields)->toBeNull();
});

it('keeps the value of an archived custom field when another setting changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $archived = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Project)->archived()->create(['team_id' => $user->current_team_id]);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => 'planning', 'custom_fields' => [$archived->id => 'kept']]);

    actingAs($user)
        ->put(route('projects.update', $project->id), ['status' => 'at_risk'])
        ->assertSessionHasNoErrors();

    expect($project->fresh())->status->toBe('at_risk')->custom_fields->toBe([$archived->id => 'kept']);
});
