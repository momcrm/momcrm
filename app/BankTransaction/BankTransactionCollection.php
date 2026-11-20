<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, BankTransaction>
 */
final class BankTransactionCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return BankTransaction::class;
    }
}
