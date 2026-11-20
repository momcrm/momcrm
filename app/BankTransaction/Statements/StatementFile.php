<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountKindEnum;

/**
 * Reads an uploaded statement file for a bank account: a BPAY biller file
 * for a BPAY account, otherwise OFX or QFX when the file says it is, or CSV.
 */
final class StatementFile
{
    /**
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public static function read(string $fileName, string $contents, BankAccount $account, ?CsvColumns $columns = null): ReadStatement
    {
        if ($account->getKind()->toEnum() === BankAccountKindEnum::Bpay) {
            return BpayStatement::fromString($fileName, $contents)->read($account->getCurrency()->toEnum());
        }

        if (OfxStatement::recognises($contents)) {
            return OfxStatement::fromString($fileName, $contents)->read($account->getCurrency()->toEnum());
        }

        return CsvStatement::fromString($fileName, $contents)->read(
            currency: $account->getCurrency()->toEnum(),
            given: $columns,
            saved: $account->getCsvColumns()->toColumns(),
        );
    }
}
