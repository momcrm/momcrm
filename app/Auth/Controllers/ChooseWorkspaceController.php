<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Actions\FindProvenEmail;
use App\Auth\Actions\LogInUser;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Signs in as staff from "Where to?", for an address that is both a staff
 * member's and a customer's contact.
 */
final class ChooseWorkspaceController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $email = FindProvenEmail::forRequest($request);
        $user = $email === null
            ? null
            : UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

        if ($user === null) {
            throw new NotFoundHttpException;
        }

        LinkSocialAccount::fromSession($request->session(), $user);

        return LogInUser::andRedirect($request, $user);
    }
}
