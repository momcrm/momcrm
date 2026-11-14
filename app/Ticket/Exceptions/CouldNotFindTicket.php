<?php

declare(strict_types=1);

namespace App\Ticket\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindTicket extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's tickets are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Ticket not found.');
    }
}
