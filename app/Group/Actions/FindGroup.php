<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\GroupEloquentModel;
use App\Group\GroupRoleEnum;
use App\Group\Properties\Name;
use App\Group\Properties\TeamId;
use App\Shared\Table;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FindGroup
{
    /**
     * Every group in the team with its members' ids, for pickers (which can
     * offer a ticket's group's members first) and the sidebar.
     *
     * @return Collection<int, array{id: string, name: string, hue: ?int, icon: ?string, member_ids: list<string>}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        $groups = GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get();
        $memberIds = DB::table(Table::GroupUser->value)
            ->whereIn('group_id', $groups->modelKeys())
            ->get(['group_id', 'user_id'])
            ->groupBy('group_id');

        return $groups->map(fn (GroupEloquentModel $group): array => [
            'id' => $group->id,
            'name' => $group->name,
            'hue' => $group->hue,
            'icon' => $group->icon,
            'member_ids' => Types::stringList($memberIds->get($group->id, collect())->pluck('user_id')),
        ]);
    }

    /**
     * A group's members by name, with their role in the group.
     *
     * @return Collection<int, array{id: string, name: string, email: string, role: string}>
     */
    public static function membersOf(string $groupId): Collection
    {
        return UserEloquentModel::query()
            ->join(Table::GroupUser->value, Table::GroupUser->value.'.user_id', '=', Table::Users->value.'.id')
            ->where(Table::GroupUser->value.'.group_id', $groupId)
            ->orderBy(Table::Users->value.'.name')
            ->get([Table::Users->value.'.id', Table::Users->value.'.name', Table::Users->value.'.email', Table::GroupUser->value.'.role'])
            ->map(fn (UserEloquentModel $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => (GroupRoleEnum::tryFrom(Types::string($member->getAttribute('role'))) ?? GroupRoleEnum::Member)->value,
            ])
            ->values();
    }
}
