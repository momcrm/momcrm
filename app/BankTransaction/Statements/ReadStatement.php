<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

/**
 * What was read from one statement file: its transactions, and how many
 * rows were passed over because they weren't transactions (headings,
 * opening balances, totals). A CSV file also says which columns it was read
 * with, so they can be kept for the next file.
 */
final readonly class ReadStatement
{
    /**
     * @param  list<StatementLine>  $lines
     */
    public function __construct(
        public array $lines,
        public int $unreadRows = 0,
        public ?CsvColumns $columns = null,
    ) {}
}
