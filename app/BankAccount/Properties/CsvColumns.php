<?php

declare(strict_types=1);

namespace App\BankAccount\Properties;

use App\BankTransaction\Statements\CsvColumns as Columns;
use Mom\Data\AbstractCollection;

/**
 * Which columns of the account's CSV statements hold what, as chosen the
 * first time a file couldn't be read on its own. Kept so the next file in
 * the same layout imports without asking.
 */
final class CsvColumns extends AbstractCollection
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'csv_columns';
    }

    public function toColumns(): ?Columns
    {
        return $this->isNull() ? null : Columns::fromStored($this->toCollection()->all());
    }
}
