<?php

declare(strict_types=1);

namespace App\Shift\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindShift extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's shifts are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Shift not found.');
    }
}
