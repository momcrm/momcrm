<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\Exceptions\CouldNotFindArticle;
use App\Article\Properties\LastReviewedOn;
use App\Article\Properties\NextReviewOn;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Says the article was checked today and is still right, and when to check
 * it next. It changes no version.
 */
final class ReviewArticleFormRequest extends FormRequest
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

        if ($this->article->isNull() || $this->article->isArchived()) {
            throw CouldNotFindArticle::notFound();
        }

        return Gate::authorize('update', $this->article);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            NextReviewOn::getNameForValidation() => ['nullable', 'date', 'after:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            NextReviewOn::getNameForValidation().'.after' => __('Choose a day after today.'),
        ];
    }

    public function process(): RedirectResponse
    {
        ArticleEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->article->getId()->toString())->update([
            LastReviewedOn::getDatabaseTableColumnName() => CarbonImmutable::today()->toDateString(),
            NextReviewOn::getDatabaseTableColumnName() => $this->input(NextReviewOn::getNameForValidation()) ?: null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title marked as reviewed.', ['title' => $this->article->getTitle()->toString()])]);

        return back();
    }
}
