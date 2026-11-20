<?php

declare(strict_types=1);

namespace App\ArticleLink\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindArticleLink extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so records someone can't see are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Not found.');
    }
}
