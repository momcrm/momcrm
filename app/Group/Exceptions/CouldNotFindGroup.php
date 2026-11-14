<?php

declare(strict_types=1);

namespace App\Group\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindGroup extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's groups are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Group not found.');
    }
}
