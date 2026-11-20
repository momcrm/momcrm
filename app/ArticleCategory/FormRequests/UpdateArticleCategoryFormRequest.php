<?php

declare(strict_types=1);

namespace App\ArticleCategory\FormRequests;

use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\Actions\UpdateArticleCategory;
use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryAccess;
use App\ArticleCategory\ArticleCategoryValidationRules;
use App\ArticleCategory\Exceptions\CouldNotFindArticleCategory;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\ParentId;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Renames a category or moves it under another one.
 */
final class UpdateArticleCategoryFormRequest extends FormRequest
{
    public ArticleCategory $category;

    public function __construct()
    {
        parent::__construct();

        $this->category = ArticleCategory::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindArticleCategory
     */
    public function authorize(): Response
    {
        $this->category = FindArticleCategory::byIdForTeam((string) $this->route('category'), ArticleCategoryAccess::team($this)->getId()->toString());

        if ($this->category->isNull()) {
            throw CouldNotFindArticleCategory::notFound();
        }

        return ArticleCategoryAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ArticleCategoryValidationRules::for($this->category->getTeamId()->toString(), $this->input(ParentId::getNameForValidation()), $this->category);
    }

    public function process(): RedirectResponse
    {
        $category = UpdateArticleCategory::fromArticleCategory(
            $this->category
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setParentId($this->input(ParentId::getNameForValidation()) ?: null),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $category->getName()->toString()])]);

        return back();
    }
}
