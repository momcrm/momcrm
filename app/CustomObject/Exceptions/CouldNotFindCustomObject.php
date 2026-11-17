<?php

declare(strict_types=1);

namespace App\CustomObject\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindCustomObject extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's objects are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Custom object not found.');
    }
}
