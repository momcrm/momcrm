<?php

declare(strict_types=1);

namespace App\Inbox;

use App\User\User;
use App\User\UserEloquentModel;

/**
 * Which kinds of inbox notification someone is also emailed. Until they
 * choose, it is the ones that ask something of them: mentions and
 * assignments.
 */
final class NotificationEmails
{
    /**
     * @return list<InboxKindEnum>
     */
    public static function defaults(): array
    {
        return [InboxKindEnum::Mention, InboxKindEnum::Assignment];
    }

    /**
     * @return list<InboxKindEnum>
     */
    public static function kindsFor(User $user): array
    {
        if ($user->getEmailedNotifications()->isNull()) {
            return self::defaults();
        }

        $chosen = $user->getEmailedNotifications()->toArray();

        return array_values(array_filter(
            InboxKindEnum::cases(),
            fn (InboxKindEnum $kind): bool => in_array($kind->value, $chosen, true),
        ));
    }

    public static function isWantedBy(UserEloquentModel $user, InboxKindEnum $kind): bool
    {
        return in_array($kind, self::kindsFor(User::fromEloquentModel($user)), true);
    }
}
