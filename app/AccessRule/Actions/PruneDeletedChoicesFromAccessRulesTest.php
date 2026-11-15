<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Actions\PruneDeletedChoicesFromAccessRules;
use App\Choice\Actions\DeleteChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Team\TeamEloquentModel;

/**
 * A rule on tickets with one condition, for the team.
 */
function ticketRuleWith(string $teamId, string $attribute, string $operator, string|array $value): AccessRuleEloquentModel
{
    return AccessRuleEloquentModel::factory()->create([
        'team_id' => $teamId,
        'resources' => ['ticket'],
        'conditions' => [
            ['attribute' => 'subject.team_role', 'operator' => 'in', 'value' => ['member']],
            ['attribute' => $attribute, 'operator' => $operator, 'value' => $value],
        ],
    ]);
}

it('takes a deleted status out of rule conditions when it is deleted, without changing what the rules do', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $deleted = ticketStatusId($team->id, 'Waiting on supplier');
    $new = ticketStatusId($team->id, 'New');
    $anyOf = ticketRuleWith($team->id, 'ticket.status', 'in', [$new, $deleted]);
    $onlyDeleted = ticketRuleWith($team->id, 'ticket.status', 'in', [$deleted]);
    $is = ticketRuleWith($team->id, 'ticket.status', 'equals', $deleted);
    $isNot = ticketRuleWith($team->id, 'ticket.status', 'not_equals', $deleted);
    $noneOf = ticketRuleWith($team->id, 'ticket.status', 'not_in', [$new, $deleted]);
    $untouched = ticketRuleWith($team->id, 'ticket.status', 'in', [$new]);
    $role = ['attribute' => 'subject.team_role', 'operator' => 'in', 'value' => ['member']];

    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($deleted)->update(['archived_at' => now()]);
    DeleteChoice::fromChoice(FindChoice::byIdForTeam($deleted, $team->id));

    expect($anyOf->fresh())
        ->conditions->toEqual([$role, ['attribute' => 'ticket.status', 'operator' => 'in', 'value' => [$new]]])
        ->is_enabled->toBeTrue()
        ->and($onlyDeleted->fresh())
        ->conditions->toEqual([$role, ['attribute' => 'ticket.status', 'operator' => 'in', 'value' => []]])
        ->is_enabled->toBeFalse()
        ->and($is->fresh())
        ->conditions->toEqual([$role, ['attribute' => 'ticket.status', 'operator' => 'equals', 'value' => null]])
        ->is_enabled->toBeFalse()
        ->and($isNot->fresh())
        ->conditions->toEqual([$role])
        ->is_enabled->toBeTrue()
        ->and($noneOf->fresh())
        ->conditions->toEqual([$role, ['attribute' => 'ticket.status', 'operator' => 'not_in', 'value' => [$new]]])
        ->and($untouched->fresh()->updated_at->equalTo($untouched->updated_at))->toBeTrue();
});

it('leaves archived choices and other conditions alone', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $archived = ticketStatusId($team->id, 'Waiting on supplier');
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($archived)->update(['archived_at' => now()]);
    $rule = ticketRuleWith($team->id, 'ticket.status', 'equals', $archived);

    expect(PruneDeletedChoicesFromAccessRules::forTeam($team->id))->toBe(0)
        ->and($rule->fresh()->is_enabled)->toBeTrue();
});
