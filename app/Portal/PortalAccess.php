<?php

declare(strict_types=1);

namespace App\Portal;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;

/**
 * What the contact signed in to the portal may see and do, by the team's
 * contact access rules. Every portal page and form asks here.
 */
final class PortalAccess
{
    /**
     * @param  AbstractData<covariant Model>|null  $record
     */
    public static function allows(AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): bool
    {
        $contact = PortalAuth::contact();

        return $contact !== null
            && ! self::isReadOnly($action)
            && AccessEvaluator::allowsContact($contact->id, $resource, $action, $record);
    }

    /**
     * Whether a staff member is viewing the portal as the contact, when
     * nothing may be changed.
     */
    public static function isViewingAs(): bool
    {
        return is_string(session(PortalSession::VIEWING_AS));
    }

    /**
     * @param  AbstractData<covariant Model>|null  $record
     *
     * @throws AuthorizationException
     */
    public static function authorize(AccessResourceEnum $resource, AccessActionEnum $action, ?AbstractData $record = null): void
    {
        if (self::isReadOnly($action)) {
            throw new AuthorizationException(__("You're viewing the portal as this contact, so nothing can be changed."));
        }

        $contact = PortalAuth::contact();
        $response = $contact === null
            ? null
            : AccessEvaluator::decideForContact($contact->id, $resource, $action, $record);

        if ($response === null || $response->denied()) {
            throw new AuthorizationException($response?->message() ?? __('This action is unauthorized.'));
        }
    }

    private static function isReadOnly(AccessActionEnum $action): bool
    {
        return $action !== AccessActionEnum::View && self::isViewingAs();
    }

    /**
     * Whether a portal section is there for the contact at all.
     */
    public static function maySeeSome(AccessResourceEnum $resource): bool
    {
        $contact = PortalAuth::contact();

        return $contact !== null && AccessEvaluator::contactMayOnSome($contact->id, $resource, AccessActionEnum::View);
    }

    /**
     * @throws AuthorizationException
     */
    public static function authorizeSection(AccessResourceEnum $resource): void
    {
        if (! self::maySeeSome($resource)) {
            throw new AuthorizationException(__('This action is unauthorized.'));
        }
    }

    /**
     * The records the contact may see, from records of their customer. When
     * no rule that applies to them looks at the record, one answer covers
     * them all; otherwise each is checked.
     *
     * @template TData of AbstractData<covariant Model>
     *
     * @param  Collection<int, TData>  $records
     * @return Collection<int, TData>
     */
    public static function visible(AccessResourceEnum $resource, Collection $records): Collection
    {
        $contact = PortalAuth::contact();

        if ($contact === null) {
            return $records->take(0);
        }

        if (! AccessEvaluator::contactDecisionDependsOnRecord($contact->id, $resource, AccessActionEnum::View)) {
            return AccessEvaluator::allowsContact($contact->id, $resource, AccessActionEnum::View) ? $records : $records->take(0);
        }

        $visible = [];

        foreach ($records as $record) {
            if (AccessEvaluator::allowsContact($contact->id, $resource, AccessActionEnum::View, $record)) {
                $visible[] = $record;
            }
        }

        return collect($visible);
    }
}
