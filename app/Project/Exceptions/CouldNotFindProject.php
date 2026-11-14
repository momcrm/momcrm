<?php

declare(strict_types=1);

namespace App\Project\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindProject extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's projects are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Project not found.');
    }
}
