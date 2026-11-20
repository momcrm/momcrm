<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use App\BankAccount\BankAccount;

/**
 * Reads an uploaded statement file for a bank account, whatever format it
 * is in.
 */
final class StatementFile
{
    /**
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public static function read(string $fileName, string $contents, BankAccount $account, ?CsvColumns $columns = null): ReadStatement
    {
        return CsvStatement::fromString($fileName, $contents)->read(
            currency: $account->getCurrency()->toEnum(),
            given: $columns,
            saved: $account->getCsvColumns()->toColumns(),
        );
    }
}
