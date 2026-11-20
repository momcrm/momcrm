<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\Properties\Id;

final readonly class CreateArticle
{
    public function __construct(
        private Article $article,
    ) {}

    public function __invoke(): Article
    {
        if ($this->article->getId()->isNull()) {
            $this->article->setId(Id::generate());
        }

        $model = ArticleEloquentModel::query()->create($this->article->forDatabaseCreate());

        return Article::fromEloquentModel($model->refresh());
    }

    public static function fromArticle(Article $article): Article
    {
        return new self($article)->handle();
    }

    public function handle(): Article
    {
        return $this->__invoke();
    }

    public function execute(): Article
    {
        return $this->__invoke();
    }
}
