<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\ArchiveArticle;
use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\Exceptions\CouldNotFindArticle;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Brings an archived article back.
 */
final class RestoreArticleFormRequest extends FormRequest
{
    public Article $article;

    public function __construct()
    {
        parent::__construct();

        $this->article = Article::new();
    }

    /**
     * @throws CouldNotFindArticle
     */
    public function authorize(): Response
    {
        $this->article = FindArticle::visibleByIdForTeam((string) $this->route('article'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->article->isNull() || ! $this->article->isArchived()) {
            throw CouldNotFindArticle::notFound();
        }

        return Gate::authorize('restore', $this->article);
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
        ArchiveArticle::restore($this->article);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title restored.', ['title' => $this->article->getTitle()->toString()])]);

        return back();
    }
}
