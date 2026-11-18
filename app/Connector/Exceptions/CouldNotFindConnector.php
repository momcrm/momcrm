<?php

declare(strict_types=1);

namespace App\Connector\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindConnector extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's connectors are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Connector not found.');
    }
}
