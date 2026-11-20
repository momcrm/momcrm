<?php

declare(strict_types=1);

namespace App\BankTransaction\Exceptions;

use RuntimeException;

final class CouldNotApplyBankTransaction extends RuntimeException
{
    /**
     * Someone recorded a payment from the line first.
     */
    public static function alreadyApplied(): self
    {
        return new self(__('That line was already recorded as a payment.'));
    }
}
