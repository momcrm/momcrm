<?php

declare(strict_types=1);

namespace App\Inbox;

use App\User\UserEloquentModel;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The private channel that tells someone their inbox changed: a notification
 * arrived, a message came in, or they read something in another tab. Only
 * that person may listen.
 */
final class InboxChannel
{
    public const string NAME = 'inbox.{userId}';

    public static function for(string $userId): PrivateChannel
    {
        return new PrivateChannel(str_replace('{userId}', $userId, self::NAME));
    }

    public function join(UserEloquentModel $user, string $userId): bool
    {
        return $user->id === $userId;
    }
}
