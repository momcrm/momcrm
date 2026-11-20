<?php

declare(strict_types=1);

namespace App\Article;

use App\AccessRule\AbstractAccessPolicy;
use App\AccessRule\AccessResourceEnum;

/**
 * Writing to articles goes through the team's access rules. Who may read one
 * is decided by its classification (ArticleVisibility).
 */
final class ArticlePolicy extends AbstractAccessPolicy
{
    protected function resource(): AccessResourceEnum
    {
        return AccessResourceEnum::Article;
    }
}
