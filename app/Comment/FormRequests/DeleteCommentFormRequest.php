<?php

declare(strict_types=1);

namespace App\Comment\FormRequests;

use App\Comment\CommentEloquentModel;
use App\Comment\Exceptions\CouldNotFindComment;
use App\Comment\Properties\Id;
use App\Comment\Properties\TeamId;
use App\Comment\Properties\UserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Deletes a comment. Only its author may.
 */
final class DeleteCommentFormRequest extends FormRequest
{
    public ?CommentEloquentModel $comment = null;

    /**
     * @throws CouldNotFindComment
     */
    public function authorize(): bool
    {
        $this->comment = CommentEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(Id::getDatabaseTableColumnName(), (string) $this->route('comment'))
            ->where(TeamId::getDatabaseTableColumnName(), $this->user()?->current_team_id)
            ->where(UserId::getDatabaseTableColumnName(), $this->user()?->getAuthIdentifier())
            ->first();

        if ($this->comment === null) {
            throw CouldNotFindComment::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $this->comment?->delete();

        return back();
    }
}
