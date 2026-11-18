<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\Properties\MailboxId;
use App\MailboxMessage\Properties\MicrosoftId;
use App\MailboxMessage\Properties\TeamId;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindMailboxMessage
{
    /**
     * The team's copy of a message, by its Message-ID.
     */
    public static function byInternetMessageIdForTeam(?string $internetMessageId, string $teamId): MailboxMessage
    {
        if ($internetMessageId === null) {
            return MailboxMessage::new();
        }

        return MailboxMessage::fromEloquentModel(
            self::query($teamId)->where(InternetMessageId::getDatabaseTableColumnName(), $internetMessageId)->first(),
        );
    }

    /**
     * Which of these Microsoft ids are already kept for a mailbox, so mail
     * read before is not fetched again.
     *
     * @param  list<string>  $microsoftIds
     * @return list<string>
     */
    public static function knownMicrosoftIds(string $mailboxId, string $teamId, array $microsoftIds): array
    {
        if ($microsoftIds === []) {
            return [];
        }

        return Types::stringList(
            self::query($teamId)
                ->where(MailboxId::getDatabaseTableColumnName(), $mailboxId)
                ->whereIn(MicrosoftId::getDatabaseTableColumnName(), $microsoftIds)
                ->pluck(MicrosoftId::getDatabaseTableColumnName())
                ->all(),
        );
    }

    /**
     * @return Builder<MailboxMessageEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return MailboxMessageEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
