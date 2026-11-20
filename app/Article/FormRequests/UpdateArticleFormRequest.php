<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\FindArticle;
use App\Article\Actions\UpdateArticle;
use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleValidationRules;
use App\Article\Exceptions\CouldNotFindArticle;
use App\Article\Properties\Body;
use App\Article\Properties\CategoryId;
use App\Article\Properties\ChangeNote;
use App\Article\Properties\Classification;
use App\Article\Properties\CustomerId;
use App\Article\Properties\NextReviewOn;
use App\Article\Properties\Status;
use App\Article\Properties\Summary;
use App\Article\Properties\Title;
use App\Choice\LabelTargetEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Saves the editor: title, summary, body, classification and whether the
 * article is published, with a note on what changed and whether it is a
 * major change once it has been published. Stays on the editor.
 */
final class UpdateArticleFormRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge(LabelTargetEnum::sentEmpty($this));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ArticleValidationRules::forUpdate($this, $this->article->getTeamId()->toString(), (string) $this->user()?->id, $this->article->getId()->toString());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ArticleValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $wasPublished = $this->article->isPublished();
        $article = UpdateArticle::fromArticle(
            $this->article
                ->setTitle($this->string(Title::getNameForValidation())->trim()->toString())
                ->setSummary($this->string(Summary::getNameForValidation())->trim()->toString() ?: null)
                ->setBody($this->input(Body::getNameForValidation()))
                ->setClassification($this->string(Classification::getNameForValidation())->toString())
                ->setStatus($this->string(Status::getNameForValidation())->toString())
                ->setChangeNote($this->string(ChangeNote::getNameForValidation())->trim()->toString() ?: null)
                ->setCategoryId($this->input(CategoryId::getNameForValidation()) ?: null)
                ->setCustomerId($this->input(CustomerId::getNameForValidation()) ?: null)
                ->setNextReviewOn($this->input(NextReviewOn::getNameForValidation()) ?: null),
            $this->user()?->id,
            $this->boolean('is_major'),
        );

        LabelTargetEnum::syncFrom($this->validated(), ArticleEloquentModel::query()->withoutGlobalScopes()->findOrFail($article->getId()->toString())->labels());

        $message = $article->isPublished() && ! $wasPublished ? ':title published.' : ':title saved.';
        Inertia::flash('toast', ['type' => 'success', 'message' => __($message, ['title' => $article->getTitle()->toString()])]);

        return back();
    }
}
