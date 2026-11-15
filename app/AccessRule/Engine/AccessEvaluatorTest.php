<?php

declare(strict_types=1);

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Engine\AccessEvaluator;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

/**
 * A team with no access rules at all, its owner, and a person in it with the
 * given role.
 *
 * @return array{owner: UserEloquentModel, person: UserEloquentModel, teamId: string}
 */
function teamWithoutRules(TeamRoleEnum $role = TeamRoleEnum::Member): array
{
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = (string) $owner->current_team_id;
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $teamId)->delete();
    $person = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    CreateTeamUser::fromIds(teamId: $teamId, userId: $person->id, role: $role);

    return ['owner' => $owner, 'person' => $person, 'teamId' => $teamId];
}

/**
 * @param  array<int, array{attribute: string, operator: string, value?: string|array<int, string>|null}>  $conditions
 * @param  array<int, string>  $resources
 * @param  array<int, string>  $actions
 */
function accessRule(string $teamId, string $effect = 'allow', array $conditions = [], array $resources = [], array $actions = [], bool $enabled = true): AccessRuleEloquentModel
{
    return AccessRuleEloquentModel::factory()->create([
        'team_id' => $teamId,
        'effect' => $effect,
        'conditions' => $conditions,
        'resources' => $resources,
        'actions' => $actions,
        'is_enabled' => $enabled,
        'name' => "{$effect} rule",
    ]);
}

it('refuses everything when no rule allows it, except to the owner', function (): void {
    ['owner' => $owner, 'person' => $person] = teamWithoutRules(TeamRoleEnum::Admin);

    expect(AccessEvaluator::decide($person, AccessResourceEnum::Project, AccessActionEnum::Create)->message())->toBe('No access rule allows you to do this.')
        ->and(AccessEvaluator::allows($owner, AccessResourceEnum::Project, AccessActionEnum::Create))->toBeTrue();
});

it('lets the owner act even when a rule denies everyone', function (): void {
    ['owner' => $owner, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, 'deny');

    expect(AccessEvaluator::allows($owner, AccessResourceEnum::Ticket, AccessActionEnum::Delete))->toBeTrue();
});

it('refuses people outside the team whatever the rules say', function (): void {
    ['teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId);
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);

    expect(AccessEvaluator::allows($outsider, AccessResourceEnum::Ticket, AccessActionEnum::Create))->toBeFalse();
});

it('lets a matching deny rule win over an allow rule and names it', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId);
    $deny = accessRule($teamId, 'deny', resources: ['project'], actions: ['delete']);

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update))->toBeTrue()
        ->and(AccessEvaluator::decide($person, AccessResourceEnum::Project, AccessActionEnum::Delete)->message())
        ->toBe("The access rule \"{$deny->name}\" does not allow this.")
        ->and(AccessEvaluator::allows($person, AccessResourceEnum::Ticket, AccessActionEnum::Delete))->toBeTrue();
});

it('ignores disabled rules', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, enabled: false);

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update))->toBeFalse();
});

it('applies a rule only when all of its conditions hold', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, conditions: [
        ['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member'],
        ['attribute' => 'project.status', 'operator' => 'in', 'value' => ['planning', 'in_progress']],
    ], resources: ['project']);
    $planning = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'status' => 'planning']));
    $completed = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'status' => 'completed']));

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update, $planning))->toBeTrue()
        ->and(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update, $completed))->toBeFalse();
});

it('compares values with each operator', function (string $operator, string|array|null $value, bool $allowed): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, conditions: [['attribute' => 'project.status', 'operator' => $operator, 'value' => $value]], resources: ['project']);
    $project = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'status' => 'planning']));

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update, $project))->toBe($allowed);
})->with([
    'is, matching' => ['equals', 'planning', true],
    'is, not matching' => ['equals', 'completed', false],
    'is not, matching' => ['not_equals', 'planning', false],
    'is not, not matching' => ['not_equals', 'completed', true],
    'is any of, matching' => ['in', ['completed', 'planning'], true],
    'is any of, not matching' => ['in', ['completed', 'on_hold'], false],
    'is none of, matching' => ['not_in', ['completed', 'planning'], false],
    'is none of, not matching' => ['not_in', ['completed', 'on_hold'], true],
]);

it('checks yes/no attributes with is yes and is no', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, conditions: [['attribute' => 'project.subject_is_lead', 'operator' => 'is_true']], resources: ['project']);
    accessRule($teamId, conditions: [['attribute' => 'project.is_archived', 'operator' => 'is_false']], resources: ['project'], actions: ['archive']);
    $led = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'lead_id' => $person->id]));
    $other = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId]));

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update, $led))->toBeTrue()
        ->and(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Update, $other))->toBeFalse()
        ->and(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Archive, $other))->toBeTrue();
});

it('treats record conditions as not holding while creating', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, resources: ['project']);
    accessRule($teamId, 'deny', conditions: [['attribute' => 'project.status', 'operator' => 'not_equals', 'value' => 'planning']], resources: ['project']);

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Create))->toBeTrue();
});

it('lets a broken condition only take access away', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, conditions: [['attribute' => 'project.unknown', 'operator' => 'equals', 'value' => 'x']], resources: ['project']);

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Create))->toBeFalse();

    ['person' => $other, 'teamId' => $otherTeamId] = teamWithoutRules();
    accessRule($otherTeamId, resources: ['ticket']);
    accessRule($otherTeamId, 'deny', conditions: [['attribute' => 'subject.team_role', 'operator' => 'is_true']], resources: ['ticket']);

    expect(AccessEvaluator::allows($other, AccessResourceEnum::Ticket, AccessActionEnum::Create))->toBeFalse();
});

it('ignores a rule naming only resources the code no longer knows', function (): void {
    ['person' => $person, 'teamId' => $teamId] = teamWithoutRules();
    accessRule($teamId, resources: ['spaceship']);

    expect(AccessEvaluator::allows($person, AccessResourceEnum::Project, AccessActionEnum::Create))->toBeFalse();
});
