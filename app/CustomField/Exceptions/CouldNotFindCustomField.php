<?php

declare(strict_types=1);

namespace App\CustomField\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindCustomField extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's fields are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Custom field not found.');
    }
}
