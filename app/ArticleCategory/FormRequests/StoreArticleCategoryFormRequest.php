<?php

declare(strict_types=1);

namespace App\ArticleCategory\FormRequests;

use App\ArticleCategory\Actions\CreateArticleCategory;
use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryAccess;
use App\ArticleCategory\ArticleCategoryValidationRules;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\ParentId;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreArticleCategoryFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return ArticleCategoryAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ArticleCategoryValidationRules::for((string) $this->user()?->current_team_id, $this->input(ParentId::getNameForValidation()));
    }

    public function process(): RedirectResponse
    {
        $category = CreateArticleCategory::fromArticleCategory(
            ArticleCategory::new()
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setParentId($this->input(ParentId::getNameForValidation()) ?: null)
                ->setTeamId($this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $category->getName()->toString()])]);

        return back();
    }
}
