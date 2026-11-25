<?php

declare(strict_types=1);

namespace App\Comment\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Comment\Comment;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\MentionedInCommentNotification;
use App\Comment\Notifications\TicketCommentedNotification;
use App\Comment\Properties\Id;
use App\Inbox\Actions\NotifyTicketWatchers;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Adds a comment to a ticket. Everyone @mentioned is told so; the ticket's
 * other watchers are told there's a new comment. A comment from the customer,
 * by email or in the portal, has no author in the team, so its sender is
 * named in their place, and while nobody is assigned the ticket the people
 * who should pick it up are told too.
 */
final readonly class CreateComment
{
    public function __construct(
        private Comment $comment,
    ) {}

    public function __invoke(): Comment
    {
        return DB::transaction(function (): Comment {
            if ($this->comment->getId()->isNull()) {
                $this->comment->setId(Id::generate());
            }

            $model = CommentEloquentModel::query()->create($this->comment->forDatabaseCreate());
            $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->findOrFail($model->ticket_id);
            $author = $model->user_id === null ? null : UserEloquentModel::query()->find($model->user_id);
            $sender = $model->sender_name ?? $model->sender_email;
            $excerpt = Str::limit($model->body, 140);
            $mentionedIds = array_values(array_diff($model->mentions ?? [], [$model->user_id]));

            RecordActivity::for(
                type: ActivityTypeEnum::TicketCommented,
                userId: $model->user_id,
                teamId: $model->team_id,
                subject: $ticket,
                details: array_filter([
                    'number' => $ticket->number,
                    'title' => $ticket->title,
                    'actor_name' => $author === null ? $sender : null,
                ], fn (mixed $value): bool => $value !== null),
            );

            $mentioned = UserEloquentModel::query()->whereIn('id', $mentionedIds)->get();

            if ($mentioned->isNotEmpty()) {
                Notification::send($mentioned, new MentionedInCommentNotification($ticket, $author, $excerpt));
            }

            if ($model->user_id === null) {
                NotifyTicketWatchers::sendFromCustomer($ticket, new TicketCommentedNotification($ticket, null, $excerpt, senderName: $sender, isFromPortal: $model->contact_id !== null));
            } else {
                NotifyTicketWatchers::send($ticket, new TicketCommentedNotification($ticket, $author, $excerpt), actorId: $model->user_id, except: $mentionedIds);
            }

            return Comment::fromEloquentModel($model->refresh());
        });
    }

    public static function fromComment(Comment $comment): Comment
    {
        return new self($comment)->handle();
    }

    public function handle(): Comment
    {
        return $this->__invoke();
    }

    public function execute(): Comment
    {
        return $this->__invoke();
    }
}
