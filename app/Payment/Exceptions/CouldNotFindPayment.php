<?php

declare(strict_types=1);

namespace App\Payment\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindPayment extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's payments are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Payment not found.');
    }
}
