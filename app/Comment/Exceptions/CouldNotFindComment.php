<?php

declare(strict_types=1);

namespace App\Comment\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindComment extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's comments are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Comment not found.');
    }
}
