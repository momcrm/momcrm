<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use RuntimeException;

/**
 * A CSV statement whose columns can't be told from the file alone, such as
 * one without headings, or with dates that read the same day-first and
 * month-first. Carries what the person needs to say which column is which.
 */
final class StatementNeedsColumns extends RuntimeException
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        public readonly string $fileName,
        public readonly array $headings,
        public readonly array $rows,
        public readonly CsvColumns $guess,
    ) {
        parent::__construct(__('Say which columns of :file hold what.', ['file' => $fileName]));
    }

    /**
     * @return array{file: string, headings: list<string>, rows: list<list<string>>, guess: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->fileName,
            'headings' => $this->headings,
            'rows' => $this->rows,
            'guess' => $this->guess->toStored(),
        ];
    }
}
