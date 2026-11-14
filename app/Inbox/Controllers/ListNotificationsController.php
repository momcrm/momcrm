<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\Actions\FindNotifications;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every notification in one column, to read, mark or clear.
 */
final class ListNotificationsController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();

        return Inertia::render('notifications', [
            'notifications' => FindNotifications::forUser($user),
        ]);
    }
}
