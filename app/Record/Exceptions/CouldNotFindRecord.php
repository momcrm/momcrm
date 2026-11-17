<?php

declare(strict_types=1);

namespace App\Record\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindRecord extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's records are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Record not found.');
    }
}
