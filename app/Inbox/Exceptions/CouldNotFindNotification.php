<?php

declare(strict_types=1);

namespace App\Inbox\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindNotification extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's notifications are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Notification not found.');
    }
}
