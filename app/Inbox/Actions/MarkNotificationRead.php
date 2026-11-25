<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\Events\InboxChanged;
use App\Inbox\InboxNotifications;
use App\User\UserEloquentModel;

/**
 * Marks one of someone's notifications read, or unread again.
 */
final class MarkNotificationRead
{
    public static function for(UserEloquentModel $user, string $id, bool $read = true): void
    {
        InboxNotifications::query($user)->whereKey($id)->update(['read_at' => $read ? now() : null]);

        InboxChanged::later($user->id);
    }
}
