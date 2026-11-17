<?php

declare(strict_types=1);

namespace App\Comment\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessEvaluator;
use App\Comment\Comment;
use App\Comment\CommentEloquentModel;
use App\Comment\CommentRelationship;
use App\Comment\Properties\RecordId;
use App\User\UserEloquentModel;

final class FindComments
{
    /**
     * The comments on a custom object's record, oldest first, as a
     * conversation shows them. Only a comment's author may delete it, and
     * only if the access rules let them.
     *
     * @return list<array{id: string, body: string, author_id: ?string, author_name: ?string, mentions: array<int, string>, created_at: ?string, can_delete: bool}>
     */
    public static function itemsForRecord(string $recordId, UserEloquentModel $viewer): array
    {
        return array_values(CommentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(CommentRelationship::Author->value)
            ->where(RecordId::getDatabaseTableColumnName(), $recordId)
            ->oldest()
            ->get()
            ->map(fn (CommentEloquentModel $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author_id' => $comment->user_id,
                'author_name' => $comment->author?->name,
                'mentions' => $comment->mentions ?? [],
                'created_at' => $comment->created_at?->toIso8601String(),
                'can_delete' => $comment->user_id === $viewer->id
                    && AccessEvaluator::allows($viewer, AccessResourceEnum::Comment, AccessActionEnum::Delete, Comment::fromEloquentModel($comment)),
            ])
            ->all());
    }
}
