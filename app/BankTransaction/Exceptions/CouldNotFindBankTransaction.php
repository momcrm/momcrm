<?php

declare(strict_types=1);

namespace App\BankTransaction\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindBankTransaction extends NotFoundHttpException
{
    /**
     * A 404 rather than a 403, so another team's lines are not revealed.
     */
    public static function notFound(): self
    {
        return new self('Bank feed line not found.');
    }
}
