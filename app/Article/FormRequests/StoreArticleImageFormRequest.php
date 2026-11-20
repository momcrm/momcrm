<?php

declare(strict_types=1);

namespace App\Article\FormRequests;

use App\Article\Actions\FindArticle;
use App\Article\Actions\StoreArticleImage;
use App\Article\Article;
use App\Article\ArticleImages;
use App\Article\Exceptions\CouldNotFindArticle;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Takes a picture pasted, dropped or chosen in the editor, and answers with
 * the address to put in the body.
 */
final class StoreArticleImageFormRequest extends FormRequest
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
            'image' => ['required', 'file', 'mimetypes:'.implode(',', ArticleImages::MIME_TYPES), 'max:'.ArticleImages::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.mimetypes' => __('Pictures can be PNG, JPEG, GIF or WebP.'),
            'image.max' => __('Pictures can be up to 10 MB.'),
        ];
    }

    public function process(): JsonResponse
    {
        $image = StoreArticleImage::for($this->file('image'), $this->article, $this->user()?->id);

        return response()->json([
            'src' => ArticleImages::path($this->article->getId()->toString(), $image->getId()->toString()),
        ], 201);
    }
}
