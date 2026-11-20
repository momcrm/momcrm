<?php

declare(strict_types=1);

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Actions\FindAccessRules;
use App\AccessRule\Engine\AccessEvaluator;
use App\AccessRule\Engine\ContactAccessSubject;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
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

/**
 * A team with no access rules, one of its customers and a contact there with
 * portal access.
 *
 * @return array{owner: UserEloquentModel, contact: ContactEloquentModel, teamId: string}
 */
function contactWithoutRules(array $attributes = []): array
{
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = (string) $owner->current_team_id;
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $teamId)->delete();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId]);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['team_id' => $teamId, 'customer_id' => $customer->id, ...$attributes]);

    return ['owner' => $owner, 'contact' => $contact, 'teamId' => $teamId];
}

it('keeps staff rules and contact rules apart', function (): void {
    ['contact' => $contact, 'teamId' => $teamId] = contactWithoutRules();
    $member = UserEloquentModel::factory()->inTeam($teamId)->create();
    accessRule($teamId);
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $teamId, 'resources' => ['invoice'], 'actions' => ['view']]);

    expect(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::Create))->toBeFalse()
        ->and(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Invoice, AccessActionEnum::View))->toBeTrue()
        ->and(AccessEvaluator::allows($member, AccessResourceEnum::Ticket, AccessActionEnum::Create))->toBeTrue()
        ->and(AccessEvaluator::allows($member, AccessResourceEnum::Invoice, AccessActionEnum::View))->toBeFalse();
});

it('lets a contact do nothing once their portal access is off, whatever the rules say', function (): void {
    ['contact' => $contact, 'teamId' => $teamId] = contactWithoutRules(['portal_access' => false]);
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $teamId]);

    expect(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View))->toBeFalse();
});

it('never lets a contact take actions only staff have, even under a rule covering everything', function (): void {
    ['contact' => $contact, 'teamId' => $teamId] = contactWithoutRules();
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $teamId]);

    expect(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::Delete))->toBeFalse()
        ->and(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Customer, AccessActionEnum::Update))->toBeFalse()
        ->and(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::Close))->toBeTrue();
});

it('checks what is known about the contact and whether they raised the ticket', function (): void {
    ['contact' => $contact, 'teamId' => $teamId] = contactWithoutRules();
    $billing = FindChoice::byNameForTeam($teamId, ChoiceListEnum::ContactRole, 'Billing');
    AccessRuleEloquentModel::factory()->forContacts()->create([
        'team_id' => $teamId,
        'resources' => ['invoice'],
        'actions' => ['view'],
        'conditions' => [['attribute' => 'contact.role', 'operator' => 'equals', 'value' => $billing->getId()->toString()]],
    ]);
    AccessRuleEloquentModel::factory()->forContacts()->create([
        'team_id' => $teamId,
        'resources' => ['ticket'],
        'actions' => ['view'],
        'conditions' => [['attribute' => 'ticket.subject_is_requester', 'operator' => 'is_true']],
    ]);
    $theirs = Ticket::fromEloquentModel(TicketEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => $contact->customer_id, 'contact_id' => $contact->id]));
    $colleagues = Ticket::fromEloquentModel(TicketEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => $contact->customer_id]));

    expect(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Invoice, AccessActionEnum::View))->toBeFalse()
        ->and(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View, $theirs))->toBeTrue()
        ->and(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View, $colleagues))->toBeFalse()
        ->and(AccessEvaluator::contactMayOnSome($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View))->toBeTrue()
        ->and(AccessEvaluator::contactDecisionDependsOnRecord($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View))->toBeTrue();

    $contact->update(['contact_role_id' => $billing->getId()->toString()]);
    ContactAccessSubject::forget($contact->id);

    expect(AccessEvaluator::allowsContact($contact->id, AccessResourceEnum::Invoice, AccessActionEnum::View))->toBeTrue()
        ->and(AccessEvaluator::contactDecisionDependsOnRecord($contact->id, AccessResourceEnum::Invoice, AccessActionEnum::View))->toBeFalse();
});

it('hides a section from a contact only when a deny rule about them covers all of it', function (): void {
    ['contact' => $contact, 'teamId' => $teamId] = contactWithoutRules();
    AccessRuleEloquentModel::factory()->forContacts()->create(['team_id' => $teamId, 'resources' => ['ticket'], 'actions' => ['view']]);
    $deny = AccessRuleEloquentModel::factory()->forContacts()->deny()->create([
        'team_id' => $teamId,
        'resources' => ['ticket'],
        'actions' => ['view'],
        'conditions' => [['attribute' => 'ticket.subject_is_requester', 'operator' => 'is_false']],
    ]);

    expect(AccessEvaluator::contactMayOnSome($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View))->toBeTrue();

    $deny->update(['conditions' => [['attribute' => 'contact.is_primary', 'operator' => 'is_false']]]);
    FindAccessRules::forgetForTeam($teamId);

    expect(AccessEvaluator::contactMayOnSome($contact->id, AccessResourceEnum::Ticket, AccessActionEnum::View))->toBeFalse();
});
