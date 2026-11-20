<?php

declare(strict_types=1);

namespace App\Product\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindProduct extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's products are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Product not found.');
    }
}
