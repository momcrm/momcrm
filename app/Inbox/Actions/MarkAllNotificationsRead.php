<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\InboxNotifications;
use App\User\UserEloquentModel;

/**
 * Marks all of someone's unread notifications in their team read.
 */
final class MarkAllNotificationsRead
{
    public static function for(UserEloquentModel $user): void
    {
        InboxNotifications::query($user)->whereNull('read_at')->update(['read_at' => now()]);
    }
}
