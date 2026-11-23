<?php

declare(strict_types=1);

namespace App\Holiday\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindHoliday extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's holidays are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Holiday not found.');
    }
}
