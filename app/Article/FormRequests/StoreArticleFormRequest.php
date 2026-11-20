<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\CreateArticle;
use App\Article\Article;
use App\Article\ArticleStatusEnum;
use App\Article\ArticleValidationRules;
use App\Article\Properties\CategoryId;
use App\Article\Properties\Classification;
use App\Article\Properties\CustomerId;
use App\Article\Properties\Title;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Starts an article as a draft from its title and classification, then
 * opens it in the editor.
 */
final class StoreArticleFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Article::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ArticleValidationRules::forCreate($this, $this->teamId(), (string) $this->user()?->id);
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
        $article = CreateArticle::fromArticle(
            Article::new()
                ->setTitle($this->string(Title::getNameForValidation())->trim()->toString())
                ->setClassification($this->string(Classification::getNameForValidation())->toString())
                ->setCategoryId($this->input(CategoryId::getNameForValidation()) ?: null)
                ->setCustomerId($this->input(CustomerId::getNameForValidation()) ?: null)
                ->setStatus(ArticleStatusEnum::Draft)
                ->setAuthorId($this->user()?->id)
                ->setTeamId($this->teamId()),
        );

        return to_route('articles.edit', $article->getId()->toString());
    }

    private function teamId(): string
    {
        return (string) $this->user()?->current_team_id;
    }
}
