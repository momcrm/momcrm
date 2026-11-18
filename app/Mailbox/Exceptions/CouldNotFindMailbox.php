<?php

declare(strict_types=1);

namespace App\Mailbox\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindMailbox extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's mailboxes are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Mailbox not found.');
    }
}
