<?php

declare(strict_types=1);

namespace App\TeamUser\Actions;

use App\Shared\Table;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\User\Properties\Id;
use App\User\Properties\Name;
use App\User\UserEloquentModel;
use Illuminate\Support\Collection;

final class FindTeamMembers
{
    /**
     * The team's members as id/name pairs, for pickers such as a ticket's assignee.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return UserEloquentModel::query()
            ->join(Table::TeamUser->value, UserId::getDatabaseTableColumnNameWithTable(Table::TeamUser), '=', Id::getDatabaseTableColumnNameWithTable(Table::Users))
            ->where(TeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser), $teamId)
            ->orderBy(Name::getDatabaseTableColumnNameWithTable(Table::Users))
            ->get([Id::getDatabaseTableColumnNameWithTable(Table::Users), Name::getDatabaseTableColumnNameWithTable(Table::Users)])
            ->map(fn (UserEloquentModel $user): array => ['id' => $user->id, 'name' => $user->name]);
    }
}
