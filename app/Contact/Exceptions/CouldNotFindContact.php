<?php

declare(strict_types=1);

namespace App\Contact\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindContact extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's contacts are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Contact not found.');
    }
}
