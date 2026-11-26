<?php

declare(strict_types=1);

namespace App\MailboxMessage\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindMailboxMessage extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's mail is not revealed.
     */
    public static function notFound(): self
    {
        return new self('Email not found.');
    }
}
