<?php

declare(strict_types=1);

namespace App\ArticleCategory\FormRequests;

use App\ArticleCategory\Actions\DeleteArticleCategory;
use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryAccess;
use App\ArticleCategory\Exceptions\CouldNotFindArticleCategory;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeleteArticleCategoryFormRequest extends FormRequest
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
        return [];
    }

    public function process(): RedirectResponse
    {
        DeleteArticleCategory::fromArticleCategory($this->category);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->category->getName()->toString()])]);

        return back();
    }
}
