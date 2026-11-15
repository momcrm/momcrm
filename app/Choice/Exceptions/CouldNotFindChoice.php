<?php

declare(strict_types=1);

namespace App\Choice\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindChoice extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's choices are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Choice not found.');
    }
}
