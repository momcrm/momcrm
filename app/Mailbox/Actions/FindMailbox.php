<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxCollection;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\Id;
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
