<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessCondition;
use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessOperatorEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleCollection;
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
 * creating, as there is no record yet. Staff and customer contacts each have
 * their own rules (see AccessAudienceEnum). A broken condition (an attribute the
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

        if (! $resource->supports($action)) {
            return Response::deny(__('No access rule allows you to do this.'));
        }

        return self::byRules($teamId, AccessAudienceEnum::Staff, $subject, $resource, $action, $record);
    }

    /**
     * The same decision for a customer's contact in the portal, from the
     * team's contact rules. Nobody is exempt, and a contact whose portal
     * access is off may do nothing.
     *
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function decideForContact(string $contactId, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): Response
    {
        $subject = ContactAccessSubject::for($contactId);

        if ($subject === null || ! $subject->hasPortalAccess) {
            return Response::deny(__('You no longer have access to the portal.'));
        }

        if (! $resource->supports($action, AccessAudienceEnum::Contact)) {
            return Response::deny(__('No access rule allows you to do this.'));
        }

        return self::byRules($subject->teamId, AccessAudienceEnum::Contact, $subject, $resource, $action, $record);
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function allowsContact(string $contactId, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): bool
    {
        return self::decideForContact($contactId, $resource, $action, $record)->allowed();
    }

    /**
     * Whether the contact may take the action on at least some records of
     * the kind, for showing a portal section: conditions on the record are
     * taken to hold for allow rules and not to hold for deny rules, so only
     * rules about the contact themselves decide.
     */
    public static function contactMayOnSome(string $contactId, AccessResourceEnum $resource, AccessActionEnum $action): bool
    {
        $subject = ContactAccessSubject::for($contactId);

        if ($subject === null || ! $subject->hasPortalAccess || ! $resource->supports($action, AccessAudienceEnum::Contact)) {
            return false;
        }

        $rules = self::rulesCovering($subject->teamId, AccessAudienceEnum::Contact, $resource, $action);

        $isDenied = $rules->contains(fn (AccessRule $rule): bool => $rule->getEffect()->toEnum() === AccessEffectEnum::Deny
            && self::matches($rule, $subject, null, brokenHolds: true, recordConditionsHold: false));

        return ! $isDenied && $rules->contains(fn (AccessRule $rule): bool => $rule->getEffect()->toEnum() === AccessEffectEnum::Allow
            && self::matches($rule, $subject, null, brokenHolds: false, recordConditionsHold: true));
    }

    /**
     * Whether the contact's answer can differ from one record to the next:
     * some rule that applies to them looks at the record. When it can't, one
     * decision without a record holds for all of them.
     */
    public static function contactDecisionDependsOnRecord(string $contactId, AccessResourceEnum $resource, AccessActionEnum $action): bool
    {
        $subject = ContactAccessSubject::for($contactId);

        if ($subject === null) {
            return false;
        }

        return self::rulesCovering($subject->teamId, AccessAudienceEnum::Contact, $resource, $action)
            ->contains(fn (AccessRule $rule): bool => collect($rule->conditionObjects())->contains(
                fn (AccessCondition $condition): bool => $condition->attribute?->resource() !== null,
            ) && self::matches($rule, $subject, null, brokenHolds: true, recordConditionsHold: true));
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    private static function byRules(string $teamId, AccessAudienceEnum $audience, AccessSubject|ContactAccessSubject $subject, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record): Response
    {
        $rules = self::rulesCovering($teamId, $audience, $resource, $action);

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

    private static function rulesCovering(string $teamId, AccessAudienceEnum $audience, AccessResourceEnum $resource, AccessActionEnum $action): AccessRuleCollection
    {
        return FindAccessRules::enabledForTeam($teamId, $audience)
            ->filter(fn (AccessRule $rule): bool => $rule->covers($resource, $action));
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function allows(UserEloquentModel $user, AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): bool
    {
        return self::decide($user, $resource, $action, $record)->allowed();
    }

    /**
     * With no record, $recordConditionsHold says what a condition on the
     * record counts as; null reads it, which never holds.
     *
     * @param  AbstractData<covariant Model>|null  $record
     */
    private static function matches(AccessRule $rule, AccessSubject|ContactAccessSubject $subject, ?AbstractData $record, bool $brokenHolds, ?bool $recordConditionsHold = null): bool
    {
        foreach ($rule->conditionObjects() as $condition) {
            if ($record === null && $recordConditionsHold !== null && $condition->attribute?->resource() !== null && ! $condition->isBroken()) {
                if ($recordConditionsHold) {
                    continue;
                }

                return false;
            }

            if (! self::holds($condition, $subject, $record, $brokenHolds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    private static function holds(AccessCondition $condition, AccessSubject|ContactAccessSubject $subject, ?AbstractData $record, bool $brokenHolds): bool
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
