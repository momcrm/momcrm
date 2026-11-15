<?php

declare(strict_types=1);

namespace App\Ticket;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\AccessRule\Engine\AccessEvaluator;
use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\AttachmentEloquentModel;
use App\Comment\Comment;
use App\Comment\CommentEloquentModel;
use App\Comment\CommentRelationship;
use App\Comment\Properties\TicketId as CommentTicketId;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerEloquentModel;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Label\Actions\FindLabel;
use App\Label\LabelEloquentModel;
use App\Project\Actions\FindProject;
use App\Project\ProjectEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\Scopes\CurrentTeamScope;
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

        return self::forTicket($id, $user);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forTicket(string $id, UserEloquentModel $user): ?array
    {
        $teamId = (string) $user->current_team_id;
        $userId = $user->id;

        $model = TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
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

        $ticket = Ticket::fromEloquentModel($model);

        return [
            ...$ticket->toSummary(),
            'can' => AccessAbilities::for($user, AccessResourceEnum::Ticket, $ticket),
            'attachment_can' => AccessAbilities::for($user, AccessResourceEnum::Attachment),
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
            'comments' => CommentEloquentModel::query()
                ->withoutGlobalScopes()
                ->with(CommentRelationship::Author->value)
                ->where(CommentTicketId::getDatabaseTableColumnName(), $model->id)
                ->oldest()
                ->get()
                ->map(fn (CommentEloquentModel $comment): array => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author_id' => $comment->user_id,
                    'author_name' => $comment->author?->name,
                    'mentions' => $comment->mentions ?? [],
                    'created_at' => $comment->created_at?->toIso8601String(),
                    'can_delete' => $comment->user_id === $userId
                        && AccessEvaluator::allows($user, AccessResourceEnum::Comment, AccessActionEnum::Delete, Comment::fromEloquentModel($comment)),
                ])
                ->values()
                ->all(),
            'attachments' => FindAttachment::forAttachable(MorphMapKeyEnum::Ticket, $model->id)
                ->map(fn (AttachmentEloquentModel $attachment): array => FindAttachment::listItem($attachment))
                ->values()
                ->all(),
            'activity' => ActivityEloquentModel::query()
                ->with(ActivityRelationship::Actor->value)
                ->where(SubjectType::getDatabaseTableColumnName(), MorphMapKeyEnum::Ticket->value)
                ->where(SubjectId::getDatabaseTableColumnName(), $model->id)
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (ActivityEloquentModel $activity): array => Activity::fromEloquentModel($activity)->toListItem($activity->actor?->name))
                ->values()
                ->all(),
        ];
    }

    /**
     * The pickers' choices: members, projects, labels, customers, statuses and priorities.
     *
     * @return array{options: array<string, mixed>}
     */
    public static function options(string $teamId, string $userId): array
    {
        return [
            'options' => [
                'members' => FindTeamMembers::optionsForTeam($teamId),
                'projects' => FindProject::optionsForTeam($teamId, $userId),
                'labels' => FindLabel::optionsForTeam($teamId),
                'customers' => FindCustomer::optionsForTeam($teamId),
                'statuses' => TicketStatusEnum::options(),
                'priorities' => TicketPriorityEnum::options(),
            ],
        ];
    }
}
