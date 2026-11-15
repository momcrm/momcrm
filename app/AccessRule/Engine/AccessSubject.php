<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\Group\GroupRoleEnum;
use App\Shared\RequestMemo;
use App\Shared\Table;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use Illuminate\Support\Facades\DB;

/**
 * The person an access decision is about, as rules see them: their role in
 * the team and the groups they are in or lead.
 */
final readonly class AccessSubject
{
    /**
     * @param  array<int, string>  $groupIds
     * @param  array<int, string>  $leadGroupIds
     */
    public function __construct(
        public string $userId,
        public string $teamId,
        public ?TeamRoleEnum $role,
        public array $groupIds,
        public array $leadGroupIds,
    ) {}

    /**
     * Built once per request for each person and team.
     */
    public static function for(string $userId, string $teamId): self
    {
        return RequestMemo::remember("access-subject.{$teamId}.{$userId}", function () use ($userId, $teamId): self {
            $memberships = DB::table(Table::GroupUser->value)
                ->join(Table::Groups->value, Table::Groups->value.'.id', '=', Table::GroupUser->value.'.group_id')
                ->where(Table::Groups->value.'.team_id', $teamId)
                ->where(Table::GroupUser->value.'.user_id', $userId)
                ->get([Table::GroupUser->value.'.group_id', Table::GroupUser->value.'.role']);

            return new self(
                userId: $userId,
                teamId: $teamId,
                role: FindTeamUser::byTeamAndUser(teamId: $teamId, userId: $userId)->getRole()->toNullableEnum(),
                groupIds: $memberships->pluck('group_id')->map(strval(...))->values()->all(),
                leadGroupIds: $memberships->where('role', GroupRoleEnum::Lead->value)->pluck('group_id')->map(strval(...))->values()->all(),
            );
        });
    }

    public function isTeamMember(): bool
    {
        return $this->role !== null;
    }

    public function isOwner(): bool
    {
        return $this->role === TeamRoleEnum::Owner;
    }
}
