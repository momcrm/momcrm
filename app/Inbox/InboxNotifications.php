<?php

declare(strict_types=1);

namespace App\Inbox;

use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Someone's notifications in their current team: the scope every inbox read
 * and change starts from.
 */
final class InboxNotifications
{
    /**
     * @return Builder<DatabaseNotification>
     */
    public static function query(UserEloquentModel $user): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->where('data->team_id', $user->current_team_id);
    }
}
