<?php

declare(strict_types=1);

namespace App\ArticleLink\FormRequests;

use App\Article\Actions\FindArticle;
use App\ArticleLink\Actions\CreateArticleLink;
use App\ArticleLink\ArticleLink;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\ArticleLink\Exceptions\CouldNotFindArticleLink;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Attaches an article to a ticket, asset or service. Needs update on the
 * record; the article must be one the person may read and not archived.
 */
final class StoreArticleLinkFormRequest extends FormRequest
{
    public ArticleLinkTargetEnum $target = ArticleLinkTargetEnum::Ticket;

    public string $recordId = '';

    /**
     * @throws CouldNotFindArticleLink
     */
    public function authorize(): Response
    {
        $target = ArticleLinkTargetEnum::tryFrom((string) $this->route('target'));
        $record = $target?->find((string) $this->route('record'), $this->teamId());

        if ($target === null || $record === null || $record->isNull()) {
            throw CouldNotFindArticleLink::notFound();
        }

        $this->target = $target;
        $this->recordId = (string) $this->route('record');

        return Gate::authorize('update', $record);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'article_id' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $article = FindArticle::visibleByIdForTeam(is_string($value) ? $value : null, $this->teamId(), (string) $this->user()?->id);

                    if ($article->isNull() || $article->isArchived()) {
                        $fail(__('Choose an article you can read.'));
                    }
                },
            ],
        ];
    }

    public function process(): RedirectResponse
    {
        CreateArticleLink::fromArticleLink(
            ArticleLink::new()
                ->setTeamId($this->teamId())
                ->setArticleId($this->string('article_id')->toString())
                ->setLinkableType($this->target)
                ->setLinkableId($this->recordId)
                ->setCreatedById($this->user()?->id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article attached.')]);

        return back();
    }

    private function teamId(): string
    {
        return (string) $this->user()?->current_team_id;
    }
}
