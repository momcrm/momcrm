<?php

declare(strict_types=1);

namespace App\ArticleLink\Actions;

use App\ArticleLink\ArticleLink;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleLink\Properties\ArticleId;
use App\ArticleLink\Properties\Id;
use App\ArticleLink\Properties\LinkableId;
use App\ArticleLink\Properties\LinkableType;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Attaches an article to a record, once: attaching it again changes nothing.
 */
final readonly class CreateArticleLink
{
    public function __construct(
        private ArticleLink $link,
    ) {}

    public function __invoke(): ArticleLink
    {
        $existing = ArticleLinkEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(ArticleId::getDatabaseTableColumnName(), $this->link->getArticleId()->toString())
            ->where(LinkableType::getDatabaseTableColumnName(), $this->link->getLinkableType()->toEnum()->value)
            ->where(LinkableId::getDatabaseTableColumnName(), $this->link->getLinkableId()->toString())
            ->first();

        if ($existing !== null) {
            return ArticleLink::fromEloquentModel($existing);
        }

        $model = ArticleLinkEloquentModel::query()->create($this->link->setId(Id::generate())->forDatabaseCreate());

        return ArticleLink::fromEloquentModel($model->refresh());
    }

    public static function fromArticleLink(ArticleLink $link): ArticleLink
    {
        return new self($link)->handle();
    }

    public function handle(): ArticleLink
    {
        return $this->__invoke();
    }

    public function execute(): ArticleLink
    {
        return $this->__invoke();
    }
}
