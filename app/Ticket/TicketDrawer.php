<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerEloquentModel;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Label\Actions\FindLabel;
use App\Label\LabelEloquentModel;
use App\Project\Actions\FindProject;
use App\Project\ProjectEloquentModel;
use App\TeamUser\Actions\FindTeamMembers;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\TeamId;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What the ticket side drawer shows: the ticket with everything around it, and
 * the options its inline pickers need. Pages that host the drawer open it with
 * `?ticket=<id>`.
 */
final class TicketDrawer
{
    public const string QUERY_KEY = 'ticket';

    /**
     * The opened ticket, or null when none is open or it isn't the team's.
     *
     * @return array<string, mixed>|null
     */
    public static function forRequest(Request $request): ?array
    {
        $id = $request->query(self::QUERY_KEY);

        if (! is_string($id) || ! Str::isUuid($id)) {
            return null;
        }

        /** @var UserEloquentModel $user */
        $user = $request->user();

        return self::forTicket($id, (string) $user->current_team_id, $user->id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forTicket(string $id, string $teamId, string $userId): ?array
    {
        $model = TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->with([
                TicketRelationship::Customer->value,
                TicketRelationship::Assignee->value,
                TicketRelationship::Project->value,
                TicketRelationship::Labels->value,
                TicketRelationship::Parent->value,
                TicketRelationship::Subtasks->value => fn ($query) => $query->orderBy(Number::getDatabaseTableColumnName()),
            ])
            ->find($id);

        if ($model === null) {
            return null;
        }

        $customer = $model->getRelationValue(TicketRelationship::Customer->value);
        $assignee = $model->getRelationValue(TicketRelationship::Assignee->value);
        $project = $model->getRelationValue(TicketRelationship::Project->value);
        $parent = $model->getRelationValue(TicketRelationship::Parent->value);

        return [
            ...Ticket::fromEloquentModel($model)->toSummary(),
            'customer_name' => $customer instanceof CustomerEloquentModel ? $customer->name : null,
            'assignee_name' => $assignee instanceof UserEloquentModel ? $assignee->name : null,
            'project_name' => $project instanceof ProjectEloquentModel ? $project->name : null,
            'project_color' => $project instanceof ProjectEloquentModel ? $project->color : null,
            'labels' => $model->labels->map(fn (LabelEloquentModel $label): array => ['id' => $label->id, 'name' => $label->name, 'color' => $label->color])->values()->all(),
            'parent' => $parent instanceof TicketEloquentModel ? ['id' => $parent->id, 'number' => $parent->number, 'title' => $parent->title] : null,
            'subtasks' => $model->subtasks
                ->map(fn (TicketEloquentModel $subtask): array => ['id' => $subtask->id, 'number' => $subtask->number, 'title' => $subtask->title, 'status' => $subtask->status])
                ->values()
                ->all(),
            'is_favorite' => in_array($model->id, FindFavorite::idsForUser($teamId, $userId, FavoritableTypeEnum::Ticket), true),
        ];
    }

    /**
     * The pickers' choices: members, projects, labels, customers, statuses and priorities.
     *
     * @return array{options: array<string, mixed>}
     */
    public static function options(string $teamId): array
    {
        return [
            'options' => [
                'members' => FindTeamMembers::optionsForTeam($teamId),
                'projects' => FindProject::optionsForTeam($teamId),
                'labels' => FindLabel::optionsForTeam($teamId),
                'customers' => FindCustomer::optionsForTeam($teamId),
                'statuses' => TicketStatusEnum::options(),
                'priorities' => TicketPriorityEnum::options(),
            ],
        ];
    }
}
