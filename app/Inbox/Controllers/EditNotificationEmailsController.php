<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\InboxKindEnum;
use App\Inbox\NotificationEmails;
use App\User\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings → Notifications: which kinds of inbox notification the person is
 * also emailed.
 */
final class EditNotificationEmailsController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('settings/notifications', [
            'emailed' => array_map(
                fn (InboxKindEnum $kind): string => $kind->value,
                NotificationEmails::kindsFor(User::fromEloquentModel($request->user())),
            ),
        ]);
    }
}
