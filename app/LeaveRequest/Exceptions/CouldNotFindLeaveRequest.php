<?php

declare(strict_types=1);

namespace App\LeaveRequest\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindLeaveRequest extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so other people's leave is not revealed.
     */
    public static function notFound(): self
    {
        return new self('Leave request not found.');
    }
}
