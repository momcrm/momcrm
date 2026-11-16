<?php

declare(strict_types=1);

namespace App\TeamUser\Actions;

use App\Shared\Table;
use App\TeamUser\TeamUser;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Support\Facades\DB;

/**
 * Takes a person out of a team: out of its groups and projects, off its open
 * tickets and project leads, and finally out of the team itself. Their closed
 * tickets, comments and activity stay as they were.
 */
final readonly class RemoveTeamMember
{
    public function __construct(
        private TeamUser $membership,
    ) {}

    public function __invoke(): void
    {
        $teamId = $this->membership->getTeamId()->toString();
        $userId = $this->membership->getUserId()->toString();

        DB::transaction(function () use ($teamId, $userId): void {
            DB::table(Table::GroupUser->value)
                ->where('user_id', $userId)
                ->whereIn('group_id', DB::table(Table::Groups->value)->select('id')->where('team_id', $teamId))
                ->delete();

            DB::table(Table::ProjectUser->value)
                ->where('user_id', $userId)
                ->whereIn('project_id', DB::table(Table::Projects->value)->select('id')->where('team_id', $teamId))
                ->delete();

            DB::table(Table::Projects->value)
                ->where('team_id', $teamId)
                ->where('lead_id', $userId)
                ->update(['lead_id' => null]);

            DB::table(Table::Tickets->value)
                ->where('team_id', $teamId)
                ->where('assignee_id', $userId)
                ->whereIn('status_id', TicketStatusCategoryEnum::Open->statusIds())
                ->update(['assignee_id' => null]);

            TeamUserEloquentModel::query()
                ->find($this->membership->getId()->toString())
                ?->delete();
        });
    }

    public static function fromTeamUser(TeamUser $membership): void
    {
        new self($membership)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
