<?php

declare(strict_types=1);

namespace App\Timesheet\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindTimesheet extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so other people's timesheets are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Timesheet not found.');
    }
}
