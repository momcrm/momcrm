<?php

declare(strict_types=1);

namespace App\Comment\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Comment\Comment;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\MentionedInRecordCommentNotification;
use App\Comment\Properties\Id;
use App\CustomObject\CustomObject;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Adds a comment to a custom object's record, and tells everyone @mentioned.
 * Records have no watchers, so nobody else is notified.
 */
final readonly class CreateRecordComment
{
    public function __construct(
        private Comment $comment,
        private CustomObject $object,
    ) {}

    public function __invoke(): Comment
    {
        return DB::transaction(function (): Comment {
            if ($this->comment->getId()->isNull()) {
                $this->comment->setId(Id::generate());
            }

            $model = CommentEloquentModel::query()->create($this->comment->forDatabaseCreate());
            $record = RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->comment->getRecordId()->toString());
            $author = $model->user_id === null ? null : UserEloquentModel::query()->find($model->user_id);
            $mentionedIds = array_values(array_diff($model->mentions ?? [], [$model->user_id]));

            RecordActivity::for(
                type: ActivityTypeEnum::RecordCommented,
                userId: $model->user_id,
                teamId: $model->team_id,
                subject: $record,
                details: ['name' => $record->name, 'object' => $this->object->getName()->toString()],
            );

            $mentioned = UserEloquentModel::query()->whereIn('id', $mentionedIds)->get();

            if ($mentioned->isNotEmpty()) {
                Notification::send($mentioned, new MentionedInRecordCommentNotification(
                    $record,
                    $this->object->getNamePlural()->toString(),
                    $author,
                    Str::limit($model->body, 140),
                ));
            }

            return Comment::fromEloquentModel($model->refresh());
        });
    }

    public static function fromComment(Comment $comment, CustomObject $object): Comment
    {
        return new self($comment, $object)->handle();
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
