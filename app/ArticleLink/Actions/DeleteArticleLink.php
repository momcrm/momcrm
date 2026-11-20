<?php

declare(strict_types=1);

namespace App\ArticleLink\Actions;

use App\ArticleLink\ArticleLink;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteArticleLink
{
    public function __construct(
        private ArticleLink $link,
    ) {}

    public function __invoke(): void
    {
        ArticleLinkEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->link->getId()->toString())
            ?->delete();
    }

    public static function fromArticleLink(ArticleLink $link): void
    {
        new self($link)->handle();
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
