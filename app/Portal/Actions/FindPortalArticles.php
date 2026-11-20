<?php

declare(strict_types=1);

namespace App\Portal\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleCollection;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleStatusEnum;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\Classification;
use App\Article\Properties\CustomerId;
use App\Article\Properties\Id;
use App\Article\Properties\Status;
use App\Article\Properties\TeamId;
use App\Article\Properties\Title;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The knowledge base as a customer sees it: published articles classified
 * for customers, either for every customer or for theirs alone.
 */
final class FindPortalArticles
{
    /**
     * The articles, by title, whose title has the search term if one is given.
     */
    public static function forContact(?string $search = null): ArticleCollection
    {
        return Article::collect(self::query()
            ->when($search, fn (Builder $query, string $search) => $query->where(Title::getDatabaseTableColumnName(), 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy(Title::getDatabaseTableColumnName())
            ->get()
            ->all(), 'fromEloquentModel');
    }

    /**
     * One article the contact may read; anything else is not found.
     */
    public static function visibleToContact(?string $id): Article
    {
        $article = $id === null
            ? Article::new()
            : Article::fromEloquentModel(self::query()->where(Id::getDatabaseTableColumnName(), $id)->first());

        if ($article->isNull() || ! PortalAccess::allows(AccessResourceEnum::Article, AccessActionEnum::View, $article)) {
            throw new NotFoundHttpException;
        }

        return $article;
    }

    /**
     * @return Builder<ArticleEloquentModel>
     */
    private static function query(): Builder
    {
        $contact = PortalContact::current();

        return ArticleEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $contact->getTeamId()->toString())
            ->where(Classification::getDatabaseTableColumnName(), ArticleClassificationEnum::Customer->value)
            ->where(Status::getDatabaseTableColumnName(), ArticleStatusEnum::Published->value)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->where(fn (Builder $query) => $query
                ->whereNull(CustomerId::getDatabaseTableColumnName())
                ->orWhere(CustomerId::getDatabaseTableColumnName(), $contact->getCustomerId()->toString()));
    }
}
