<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Attachment\Actions\DeleteAttachments;
use App\Shared\MorphMapKeyEnum;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteArticle
{
    public function __construct(
        private Article $article,
    ) {}

    /**
     * The article's pictures go with it.
     */
    public function __invoke(): void
    {
        DeleteAttachments::for(MorphMapKeyEnum::Article, $this->article->getId()->toString());

        ArticleEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->article->getId()->toString())
            ?->delete();
    }

    public static function fromArticle(Article $article): void
    {
        new self($article)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
