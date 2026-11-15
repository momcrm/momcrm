<?php

declare(strict_types=1);

namespace App\AccessRule\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindAccessRule extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's rules are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Access rule not found.');
    }
}
