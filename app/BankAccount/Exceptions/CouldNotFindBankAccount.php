<?php

declare(strict_types=1);

namespace App\BankAccount\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindBankAccount extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's accounts are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Bank account not found.');
    }
}
