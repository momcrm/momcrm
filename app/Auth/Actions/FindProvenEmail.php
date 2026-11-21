<?php

declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\AuthSession;
use App\Portal\PortalAccess;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The email address this browser has proven is its own: one proven with a
 * code, Google or Microsoft this session, or a signed-in staff member's
 * verified address. It decides which workspace and customer portals someone
 * may choose between.
 *
 * A contact's address on record is never used, as staff can change it. While
 * a staff member views the portal as a contact there is none.
 */
final class FindProvenEmail
{
    public static function forRequest(Request $request): ?string
    {
        if (PortalAccess::isViewingAs()) {
            return null;
        }

        $proven = Types::nullableString($request->session()->get(AuthSession::PROVEN_EMAIL));

        if ($proven !== null) {
            return $proven;
        }

        $user = Auth::guard('web')->user();

        return $user instanceof UserEloquentModel && $user->hasVerifiedEmail()
            ? mb_strtolower($user->email)
            : null;
    }
}
