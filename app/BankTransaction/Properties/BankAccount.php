<?php

declare(strict_types=1);

namespace App\BankTransaction\Properties;

use App\BankAccount\BankAccount as BankAccountData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The account the line was imported into, when loaded.
 *
 * @extends AbstractRelatedData<BankAccountData>
 */
final class BankAccount extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'bank_account';
    }

    public static function getRelationshipName(): string
    {
        return 'bankAccount';
    }

    public static function dataClass(): string
    {
        return BankAccountData::class;
    }
}
