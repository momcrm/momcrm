<?php

declare(strict_types=1);

namespace App\Secret\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindSecret extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's records are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Secret not found.');
    }
}
