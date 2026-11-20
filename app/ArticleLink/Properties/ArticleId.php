<?php

declare(strict_types=1);

namespace App\ArticleLink\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ArticleId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'article_id';
    }
}
