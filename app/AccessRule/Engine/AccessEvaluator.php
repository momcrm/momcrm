<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessCondition;
use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessOperatorEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRule;
use App\AccessRule\Actions\FindAccessRules;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractData;

/**
 * Decides whether someone may take an action on a record, from their team's
 * access rules:
 *
 * 1. The team owner may always act, so nobody can lock the team out.
 * 2. Otherwise a matching deny rule refuses.
 * 3. Otherwise a matching allow rule permits.
 * 4. Otherwise the action is refused.
 *
 * A rule matches when it covers the resource and action and all its
 * conditions hold. A condition on a record attribute does not hold while
 * creating, as there is no record yet. A broken condition (an attribute the
 * code no longer knows) holds for deny rules and fails for allow rules, so a
 * broken rule can only take access away.
 */
final class AccessEvaluator
{
    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function decide(UserEloquentModel $user, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): Response
    {
        $teamId = $user->current_team_id;

        if ($teamId === null) {
            return Response::deny('You are not in a team.');
        }

        $subject = AccessSubject::for($user->id, $teamId);

        if ($subject->isOwner()) {
            return Response::allow();
        }

        if (! $subject->isTeamMember()) {
            return Response::deny('You are not a member of this team.');
        }

        $rules = FindAccessRules::enabledForTeam($teamId)
            ->filter(fn (AccessRule $rule): bool => $rule->covers($resource, $action));

        $denying = $rules->first(fn (AccessRule $rule): bool => $rule->getEffect()->toEnum() === AccessEffectEnum::Deny
            && self::matches($rule, $subject, $record, brokenHolds: true));

        if ($denying->isNotNull()) {
            return Response::deny(__('The access rule ":name" does not allow this.', ['name' => $denying->getName()->toString()]));
        }

        $allowing = $rules->first(fn (AccessRule $rule): bool => $rule->getEffect()->toEnum() === AccessEffectEnum::Allow
            && self::matches($rule, $subject, $record, brokenHolds: false));

        return $allowing->isNotNull()
            ? Response::allow()
            : Response::deny(__('No access rule allows you to do this.'));
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function allows(UserEloquentModel $user, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): bool
    {
        return self::decide($user, $resource, $action, $record)->allowed();
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    private static function matches(AccessRule $rule, AccessSubject $subject, ?AbstractData $record, bool $brokenHolds): bool
    {
        foreach ($rule->conditionObjects() as $condition) {
            if (! self::holds($condition, $subject, $record, $brokenHolds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    private static function holds(AccessCondition $condition, AccessSubject $subject, ?AbstractData $record, bool $brokenHolds): bool
    {
        if ($condition->isBroken() || $condition->attribute === null || $condition->operator === null) {
            return $brokenHolds;
        }

        $actual = AccessAttributeResolver::resolve($condition->attribute, $subject, $record);

        if ($actual === null) {
            return false;
        }

        $actualValues = is_array($actual) ? $actual : [$actual];
        $expectedValues = is_array($condition->value) ? $condition->value : [$condition->value];

        return match ($condition->operator) {
            AccessOperatorEnum::IsTrue => $actual === true,
            AccessOperatorEnum::IsFalse => $actual === false,
            AccessOperatorEnum::Equals => in_array($condition->value, $actualValues, true),
            AccessOperatorEnum::NotEquals => ! in_array($condition->value, $actualValues, true),
            AccessOperatorEnum::In => array_intersect($actualValues, $expectedValues) !== [],
            AccessOperatorEnum::NotIn => array_intersect($actualValues, $expectedValues) === [],
        };
    }
}
