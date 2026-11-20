<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\FindArticle;
use App\Article\Actions\UpdateArticle;
use App\Article\Article;
use App\Article\Exceptions\CouldNotFindArticle;
use App\ArticleVersion\Actions\FindArticleVersion;
use App\ArticleVersion\ArticleVersion;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Brings back an earlier version's title, summary and body as the article's
 * next version, so nothing in between is lost. The classification stays as
 * it is now.
 */
final class RestoreArticleVersionFormRequest extends FormRequest
{
    public Article $article;

    public ArticleVersion $version;

    public function __construct()
    {
        parent::__construct();

        $this->article = Article::new();
        $this->version = ArticleVersion::new();
    }

    /**
     * @throws CouldNotFindArticle
     */
    public function authorize(): Response
    {
        $userId = (string) $this->user()?->id;
        $this->article = FindArticle::visibleByIdForTeam((string) $this->route('article'), $this->user()?->current_team_id, $userId);

        if ($this->article->isNull() || $this->article->isArchived()) {
            throw CouldNotFindArticle::notFound();
        }

        $this->version = FindArticleVersion::visibleByIdForArticle((string) $this->route('version'), $this->article, $userId);

        if ($this->version->isNull()) {
            throw CouldNotFindArticle::notFound();
        }

        return Gate::authorize('update', $this->article);
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
        $article = UpdateArticle::fromArticle(
            $this->article
                ->setTitle($this->version->getTitle())
                ->setSummary($this->version->getSummary())
                ->setBody($this->version->getBody())
                ->setChangeNote(__('Brought back version :version.', ['version' => $this->version->versionLabel()])),
            $this->user()?->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version :old brought back as :new.', ['old' => $this->version->versionLabel(), 'new' => $article->versionLabel()])]);

        return to_route('articles.show', $article->getId()->toString());
    }
}
