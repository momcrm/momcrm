<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageDirectionEnum;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\MailboxMessage\Properties\BodyHtml;
use App\MailboxMessage\Properties\CommentId;
use App\MailboxMessage\Properties\Direction;
use App\MailboxMessage\Properties\ExternalId;
use App\MailboxMessage\Properties\FromAddress;
use App\MailboxMessage\Properties\Id;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\Properties\MailboxId;
use App\MailboxMessage\Properties\ReceivedAt;
use App\MailboxMessage\Properties\TeamId;
use App\MailboxMessage\Properties\TicketId;
use App\Project\ProjectVisibility;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\Id as TicketKey;
use App\Ticket\Properties\TeamId as TicketTeamId;
use App\Ticket\TicketEloquentModel;
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
                ->whereNotNull(ExternalId::getDatabaseTableColumnName())
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
     * The emails on a ticket that can be shown as their sender laid them
     * out: the one that opened the ticket, and the one each reply came in.
     *
     * @return array{ticket: ?string, comments: array<string, string>}
     */
    public static function originalsOnTicket(string $ticketId, string $teamId): array
    {
        $originals = ['ticket' => null, 'comments' => []];

        $messages = self::query($teamId)
            ->where(TicketId::getDatabaseTableColumnName(), $ticketId)
            ->where(Direction::getDatabaseTableColumnName(), MailboxMessageDirectionEnum::Inbound->value)
            ->whereNotNull(BodyHtml::getDatabaseTableColumnName())
            ->oldest(ReceivedAt::getDatabaseTableColumnName())
            ->oldest(Id::getDatabaseTableColumnName())
            ->get([Id::getDatabaseTableColumnName(), CommentId::getDatabaseTableColumnName()]);

        foreach ($messages as $message) {
            if ($message->comment_id === null) {
                $originals['ticket'] ??= $message->id;
            } else {
                $originals['comments'][$message->comment_id] = $message->id;
            }
        }

        return $originals;
    }

    /**
     * An email's HTML, as it arrived, for someone who may see the ticket it
     * is on. Null when there is none for them: no such email in the team, no
     * HTML with it, or a ticket in a members-only project they are not in.
     */
    public static function htmlVisibleOnTicket(string $id, ?string $teamId, string $userId): ?string
    {
        if ($teamId === null) {
            return null;
        }

        $message = self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->where(Direction::getDatabaseTableColumnName(), MailboxMessageDirectionEnum::Inbound->value)
            ->whereNotNull(TicketId::getDatabaseTableColumnName())
            ->whereNotNull(BodyHtml::getDatabaseTableColumnName())
            ->first();

        if ($message === null) {
            return null;
        }

        $tickets = TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TicketTeamId::getDatabaseTableColumnName(), $teamId)
            ->where(TicketKey::getDatabaseTableColumnName(), $message->ticket_id);

        ProjectVisibility::constrainTickets($tickets->getQuery(), $userId, Table::Tickets->value);

        return $tickets->exists() ? $message->body_html : null;
    }

    /**
     * Which of these Microsoft ids are already kept for a mailbox, so mail
     * read before is not fetched again.
     *
     * @param  list<string>  $externalIds
     * @return list<string>
     */
    public static function knownExternalIds(string $mailboxId, string $teamId, array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }

        return Types::stringList(
            self::query($teamId)
                ->where(MailboxId::getDatabaseTableColumnName(), $mailboxId)
                ->whereIn(ExternalId::getDatabaseTableColumnName(), $externalIds)
                ->pluck(ExternalId::getDatabaseTableColumnName())
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
