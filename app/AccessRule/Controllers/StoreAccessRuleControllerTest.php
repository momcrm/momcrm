<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

/**
 * @return array<string, mixed>
 */
function accessRulePayload(array $overrides = []): array
{
    return [
        'name' => 'Leads archive their tickets',
        'effect' => 'allow',
        'resources' => ['ticket'],
        'actions' => ['archive'],
        'conditions' => [['attribute' => 'ticket.subject_is_project_lead', 'operator' => 'is_true', 'value' => 'ignored']],
        ...$overrides,
    ];
}

it('adds a rule for the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();

    actingAs($admin)
        ->post(route('access-rules.store'), accessRulePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('access-rules.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Leads archive their tickets added.']);

    $rule = AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('name', 'Leads archive their tickets')->firstOrFail();

    expect($rule)
        ->team_id->toBe($owner->current_team_id)
        ->created_by_id->toBe($admin->id)
        ->resources->toBe(['ticket'])
        ->actions->toBe(['archive'])
        ->effect->toBe('allow')
        ->is_enabled->toBeTrue()
        ->conditions->toEqual([['attribute' => 'ticket.subject_is_project_lead', 'operator' => 'is_true', 'value' => null]]);
});

it('rejects rules that do not fit together', function (array $overrides, string $errorKey): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('access-rules.store'), accessRulePayload($overrides))
        ->assertSessionHasErrors($errorKey);
})->with([
    'an action the records lack' => [['actions' => ['change_visibility']], 'actions'],
    'a record attribute on a rule for several kinds of record' => [['resources' => ['ticket', 'project']], 'conditions.0.attribute'],
    'a record attribute on a rule for everything' => [['resources' => []], 'conditions.0.attribute'],
    'an operator that does not suit the attribute' => [['conditions' => [['attribute' => 'ticket.status', 'operator' => 'is_true']]], 'conditions.0.operator'],
    'a value from outside the list' => [['conditions' => [['attribute' => 'ticket.status', 'operator' => 'equals', 'value' => 'lost']]], 'conditions.0.value'],
    'a single value where a list is needed' => [['conditions' => [['attribute' => 'ticket.status', 'operator' => 'in', 'value' => 'new']]], 'conditions.0.value'],
    'another team\'s person' => [['resources' => [], 'conditions' => [['attribute' => 'subject.user', 'operator' => 'equals', 'value' => '01a1dc8f-7713-72b5-8b66-130e19d19e4a']]], 'conditions.0.value'],
    'an unknown effect' => [['effect' => 'maybe'], 'effect'],
]);

it('adds a rule for customer contacts and goes back to their rules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('access-rules.store'), accessRulePayload([
            'audience' => 'contact',
            'name' => 'Contacts see their own tickets',
            'actions' => ['view'],
            'conditions' => [['attribute' => 'ticket.subject_is_requester', 'operator' => 'is_true']],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('access-rules.index', ['audience' => 'contact']);

    expect(AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('name', 'Contacts see their own tickets')->sole()->audience)->toBe('contact');
});

it("rejects what doesn't suit a rule's audience", function (array $overrides, string $errorKey): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('access-rules.store'), accessRulePayload($overrides))
        ->assertSessionHasErrors($errorKey);
})->with([
    'a staff action for contacts' => [['audience' => 'contact', 'conditions' => []], 'actions'],
    'records contacts never see' => [['audience' => 'contact', 'resources' => ['project'], 'actions' => [], 'conditions' => []], 'resources'],
    'a staff attribute for contacts' => [['audience' => 'contact', 'actions' => ['view'], 'conditions' => [['attribute' => 'ticket.subject_is_assignee', 'operator' => 'is_true']]], 'conditions.0.attribute'],
    'a contact attribute for staff' => [['resources' => [], 'actions' => [], 'conditions' => [['attribute' => 'contact.is_primary', 'operator' => 'is_true']]], 'conditions.0.attribute'],
    'viewing for staff' => [['actions' => ['view'], 'conditions' => []], 'actions'],
]);

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->post(route('access-rules.store'), accessRulePayload())->assertForbidden();
});
