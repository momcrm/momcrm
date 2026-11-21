<?php

declare(strict_types=1);

namespace App\Portal;

use App\Contact\ContactEloquentModel;
use App\Shared\RequestMemo;
use Illuminate\Support\Facades\Auth;

/**
 * The contact signed in to the customer portal, if any. Everything asks here
 * rather than the portal guard, which knows only the contact's id for sure.
 */
final class PortalAuth
{
    public const string GUARD = 'portal';

    public static function contact(): ?ContactEloquentModel
    {
        $id = Auth::guard(self::GUARD)->id();

        if (! is_string($id)) {
            return null;
        }

        return RequestMemo::remember("portal-contact.{$id}", fn (): ?ContactEloquentModel => ContactEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($id));
    }

    /**
     * Whether this browser is signed in as a customer's contact: signed in to
     * the portal, and not as staff unless a staff member is viewing the
     * portal as them.
     */
    public static function isSignedInAsContact(): bool
    {
        return Auth::guard(self::GUARD)->check()
            && (! Auth::guard('web')->check() || PortalAccess::isViewingAs());
    }

    /**
     * Whether this request is being served to a portal contact: the portal's
     * own pages make the portal guard the one every check uses.
     */
    public static function isServingPortal(): bool
    {
        return Auth::getDefaultDriver() === self::GUARD;
    }
}
