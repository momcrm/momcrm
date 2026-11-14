<?php

declare(strict_types=1);

namespace App\Customer\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindCustomer extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's customers are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Customer not found.');
    }
}
