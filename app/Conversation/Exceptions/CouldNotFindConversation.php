<?php

declare(strict_types=1);

namespace App\Conversation\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindConversation extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so other people's conversations are not
     * revealed.
     */
    public static function notFound(): self
    {
        return new self('Conversation not found.');
    }
}
