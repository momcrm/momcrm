<?php

declare(strict_types=1);

namespace App\Invoice\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindInvoice extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's invoices are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Invoice not found.');
    }
}
