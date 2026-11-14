<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\Actions\FindNotifications;
use App\Inbox\InboxKindEnum;
use App\Ticket\TicketDrawer;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The inbox: notifications on the left, by kind, and the conversation on the
 * selected ticket (`?ticket=`) on the right.
 */
final class ShowInboxController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $kind = InboxKindEnum::tryFrom($request->string('kind')->toString());
        $unreadOnly = $request->boolean('unread');

        return Inertia::render('inbox', [
            'notifications' => FindNotifications::forUser($user, $kind, $unreadOnly),
            'counts' => FindNotifications::unreadCounts($user),
            'filters' => ['kind' => $kind?->value, 'unread' => $unreadOnly],
            ...TicketDrawer::options((string) $user->current_team_id),
            'selected' => TicketDrawer::forRequest($request),
        ]);
    }
}
