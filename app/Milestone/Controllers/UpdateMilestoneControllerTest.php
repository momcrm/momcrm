<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a milestone, moves its date and sets a custom field, keeping the fields not sent and whether it is done', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Milestone)->create($team);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Milestone)->ofType(CustomFieldTypeEnum::Checkbox)->create($team);
    $milestone = MilestoneEloquentModel::factory()->create([
        ...$team,
        'project_id' => ProjectEloquentModel::factory()->create($team)->id,
        'name' => 'Go live',
        'due_on' => '2026-12-01',
        'completed_at' => now(),
        'custom_fields' => [$kept->id => 'kept'],
    ]);

    actingAs($user)
        ->patch(route('milestones.update', $milestone->id), ['name' => 'Launch', 'due_on' => '2026-12-15', 'custom_fields' => [$set->id => true]])
        ->assertSessionHasNoErrors();

    expect($milestone->fresh())
        ->name->toBe('Launch')
        ->completed_at->not->toBeNull()
        ->custom_fields->toEqual([$kept->id => 'kept', $set->id => true])
        ->and(substr((string) $milestone->fresh()->due_on, 0, 10))->toBe('2026-12-15');
});

it('needs a name and a due date, and a custom field value of the right type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $count = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Milestone)->ofType(CustomFieldTypeEnum::Number)->create([...$team, 'name' => 'Sign-offs']);
    $milestone = MilestoneEloquentModel::factory()->create([...$team, 'project_id' => ProjectEloquentModel::factory()->create($team)->id, 'name' => 'Go live']);

    actingAs($user)
        ->patch(route('milestones.update', $milestone->id), ['name' => '', 'custom_fields' => [$count->id => 'a few']])
        ->assertSessionHasErrors(['name', 'due_on', "custom_fields.{$count->id}" => 'The Sign-offs field must be a number.']);

    expect($milestone->fresh()->name)->toBe('Go live');
});

it('finds no milestone to change in another team, or in a members-only project the person is not in', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $secret = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'visibility' => 'restricted']);
    $hidden = MilestoneEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'project_id' => $secret->id]);
    $foreign = MilestoneEloquentModel::factory()->create();
    $change = ['name' => 'Mine', 'due_on' => '2026-12-15'];

    actingAs($member)->patch(route('milestones.update', $hidden->id), $change)->assertNotFound();
    actingAs($member)->patch(route('milestones.update', $foreign->id), $change)->assertNotFound();
});
