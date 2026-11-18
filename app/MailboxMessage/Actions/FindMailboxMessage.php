<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\MailboxMessage\Properties\CommentId;
use App\MailboxMessage\Properties\Direction;
use App\MailboxMessage\Properties\FromAddress;
use App\MailboxMessage\Properties\Id;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\Properties\MailboxId;
use App\MailboxMessage\Properties\MicrosoftId;
use App\MailboxMessage\Properties\ReceivedAt;
use App\MailboxMessage\Properties\TeamId;
use App\MailboxMessage\Properties\TicketId;
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
     * The message on a ticket that a reply follows. The ids are the reply's
     * thread, nearest first, and the nearest one the team has on a ticket wins.
     *
     * @param  list<string>  $internetMessageIds
     */
    public static function nearestOnTicket(array $internetMessageIds, string $teamId): MailboxMessage
    {
        if ($internetMessageIds === []) {
            return MailboxMessage::new();
        }

        $found = self::query($teamId)
            ->whereIn(InternetMessageId::getDatabaseTableColumnName(), $internetMessageIds)
            ->whereNotNull(TicketId::getDatabaseTableColumnName())
            ->get()
            ->keyBy(fn (MailboxMessageEloquentModel $message): string => $message->internet_message_id);

        foreach ($internetMessageIds as $id) {
            if ($found->has($id)) {
                return MailboxMessage::fromEloquentModel($found->get($id));
            }
        }

        return MailboxMessage::new();
    }

    /**
     * Whether someone at this address has already written in on the ticket.
     */
    public static function hasSenderOnTicket(string $ticketId, string $teamId, string $address): bool
    {
        return self::query($teamId)
            ->where(TicketId::getDatabaseTableColumnName(), $ticketId)
            ->where(Direction::getDatabaseTableColumnName(), MailboxMessageDirectionEnum::Inbound->value)
            ->where(FromAddress::getDatabaseTableColumnName(), $address)
            ->exists();
    }

    /**
     * A message in any team, for the job that sends it with nobody signed in.
     */
    public static function byId(?string $id): MailboxMessage
    {
        if ($id === null) {
            return MailboxMessage::new();
        }

        return MailboxMessage::fromEloquentModel(
            MailboxMessageEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->find($id),
        );
    }

    /**
     * The last message the customer sent on a ticket through a mailbox, which
     * is the one a reply from that mailbox answers.
     */
    public static function latestInboundOnTicket(string $ticketId, string $teamId, string $mailboxId): MailboxMessage
    {
        return MailboxMessage::fromEloquentModel(
            self::query($teamId)
                ->where(TicketId::getDatabaseTableColumnName(), $ticketId)
                ->where(MailboxId::getDatabaseTableColumnName(), $mailboxId)
                ->where(Direction::getDatabaseTableColumnName(), MailboxMessageDirectionEnum::Inbound->value)
                ->whereNotNull(MicrosoftId::getDatabaseTableColumnName())
                ->latest(ReceivedAt::getDatabaseTableColumnName())
                ->latest(Id::getDatabaseTableColumnName())
                ->first(),
        );
    }

    /**
     * How the replies sent from a ticket fared, by the comment each one
     * carried: whether it is waiting, sent or failed, and why it failed.
     *
     * @return array<string, array{status: string, error: ?string}>
     */
    public static function deliveriesForTicket(string $ticketId, string $teamId): array
    {
        $deliveries = [];

        $messages = self::query($teamId)
            ->where(TicketId::getDatabaseTableColumnName(), $ticketId)
            ->where(Direction::getDatabaseTableColumnName(), MailboxMessageDirectionEnum::Outbound->value)
            ->whereNotNull(CommentId::getDatabaseTableColumnName())
            ->get();

        foreach ($messages as $message) {
            $deliveries[(string) $message->comment_id] = ['status' => $message->status, 'error' => $message->error];
        }

        return $deliveries;
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
