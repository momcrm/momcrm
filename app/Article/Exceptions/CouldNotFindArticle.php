<?php

declare(strict_types=1);

namespace App\Article\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindArticle extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so articles of another team, or of a
     * classification someone may not read, are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Article not found.');
    }
}
