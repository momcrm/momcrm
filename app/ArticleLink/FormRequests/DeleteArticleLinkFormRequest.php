<?php

declare(strict_types=1);

namespace App\ArticleLink\FormRequests;

use App\Article\Actions\FindArticle;
use App\ArticleLink\Actions\DeleteArticleLink;
use App\ArticleLink\Actions\FindArticleLink;
use App\ArticleLink\ArticleLink;
use App\ArticleLink\Exceptions\CouldNotFindArticleLink;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Takes an article off a ticket, asset or service. Needs update on the
 * record, and the person must be able to see both.
 */
final class DeleteArticleLinkFormRequest extends FormRequest
{
    public ArticleLink $link;

    public function __construct()
    {
        parent::__construct();

        $this->link = ArticleLink::new();
    }

    /**
     * @throws CouldNotFindArticleLink
     */
    public function authorize(): Response
    {
        $teamId = (string) $this->user()?->current_team_id;
        $this->link = FindArticleLink::byIdForTeam((string) $this->route('link'), $teamId);

        if ($this->link->isNull()) {
            throw CouldNotFindArticleLink::notFound();
        }

        $record = $this->link->getLinkableType()->toEnum()->find($this->link->getLinkableId()->toString(), $teamId);
        $article = FindArticle::visibleByIdForTeam($this->link->getArticleId()->toString(), $teamId, (string) $this->user()?->id);

        if ($record->isNull() || $article->isNull()) {
            throw CouldNotFindArticleLink::notFound();
        }

        return Gate::authorize('update', $record);
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
        DeleteArticleLink::fromArticleLink($this->link);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article taken off.')]);

        return back();
    }
}
