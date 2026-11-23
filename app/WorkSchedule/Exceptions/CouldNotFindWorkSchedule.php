<?php

declare(strict_types=1);

namespace App\WorkSchedule\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindWorkSchedule extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's schedules are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Work schedule not found.');
    }
}
