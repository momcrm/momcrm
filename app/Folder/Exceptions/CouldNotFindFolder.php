<?php

declare(strict_types=1);

namespace App\Folder\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindFolder extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's records are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Folder not found.');
    }
}
