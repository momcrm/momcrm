<?php

declare(strict_types=1);

namespace App\Charge\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindCharge extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's charges are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Charge not found.');
    }
}
