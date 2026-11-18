<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxCollection;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MailboxStatusEnum;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\Id;
use App\Mailbox\Properties\Status;
use App\Mailbox\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindMailbox
{
    /**
     * Every mailbox the team has connected, by address.
     */
    public static function allForTeam(string $teamId): MailboxCollection
    {
        return Mailbox::collect(
            self::query()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(Address::getDatabaseTableColumnName())
                ->get()
                ->all(),
        );
    }

    public static function byIdForTeam(?string $id, ?string $teamId): Mailbox
    {
        if ($id === null || $teamId === null) {
            return Mailbox::new();
        }

        return Mailbox::fromEloquentModel(
            self::query()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->where(Id::getDatabaseTableColumnName(), $id)
                ->first(),
        );
    }

    /**
     * The mailbox a team sends from: the one asked for when it is theirs and
     * connected, otherwise the first of their connected mailboxes.
     */
    public static function toSendFromForTeam(?string $preferredId, string $teamId): Mailbox
    {
        $connected = self::allForTeam($teamId)->filter(fn (Mailbox $mailbox): bool => $mailbox->isConnected());

        $preferred = $connected->findById($preferredId);

        return $preferred->isNotNull() ? $preferred : $connected->first();
    }

    /**
     * A mailbox in any team, for the jobs that read mailboxes with nobody signed in.
     */
    public static function byId(?string $id): Mailbox
    {
        if ($id === null) {
            return Mailbox::new();
        }

        return Mailbox::fromEloquentModel(self::query()->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * Every team's mailboxes that can be read right now.
     */
    public static function connected(): MailboxCollection
    {
        return Mailbox::collect(
            self::query()
                ->where(Status::getDatabaseTableColumnName(), MailboxStatusEnum::Connected->value)
                ->orderBy(Id::getDatabaseTableColumnName())
                ->get()
                ->all(),
        );
    }

    /**
     * The mailbox with this address in any team. An address belongs to one
     * team at most, so its mail is never read twice.
     */
    public static function byAddress(string $address): Mailbox
    {
        return Mailbox::fromEloquentModel(
            self::query()->where(Address::getDatabaseTableColumnName(), mb_strtolower($address))->first(),
        );
    }

    /**
     * @return Builder<MailboxEloquentModel>
     */
    private static function query(): Builder
    {
        return MailboxEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class);
    }
}
