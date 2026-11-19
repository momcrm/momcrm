<?php

declare(strict_types=1);

namespace App\Rate\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindRate extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's rates are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Rate not found.');
    }
}
