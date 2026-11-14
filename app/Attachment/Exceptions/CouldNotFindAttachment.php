<?php

declare(strict_types=1);

namespace App\Attachment\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindAttachment extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's attachments are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Attachment not found.');
    }
}
