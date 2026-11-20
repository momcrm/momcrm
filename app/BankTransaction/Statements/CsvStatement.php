<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use App\Shared\CurrencyEnum;

/**
 * A bank statement exported as CSV. Columns are found by their headings
 * (Date, Description or Narrative, Amount or Debit and Credit), as most
 * banks write them. A file without headings, or whose dates read both
 * day-first and month-first, needs its columns said once; they are kept on
 * the account for the next file in the same layout.
 *
 * Each row's fingerprint, and how many identical rows came before it in the
 * file, tells it apart from every other, so importing an overlapping
 * statement again skips what is already in.
 */
final class CsvStatement
{
    public const int MAX_ROWS = 5000;

    private const int PREVIEW_ROWS = 5;

    /**
     * Accepted headings for each column, compared after lowercasing and
     * replacing anything that is not a letter or digit with a space.
     *
     * @var array<string, list<string>>
     */
    public const array HEADINGS = [
        'date' => ['date', 'transaction date', 'trans date', 'txn date', 'posted date', 'posting date', 'post date', 'date posted', 'process date', 'processed date', 'value date', 'effective date', 'booking date'],
        'description' => ['description', 'narrative', 'narration', 'details', 'transaction details', 'transaction description', 'memo', 'particulars', 'payee', 'payer', 'merchant name', 'other party', 'reference', 'code'],
        'amount' => ['amount', 'transaction amount', 'amount aud', 'net amount', 'value'],
        'credit' => ['credit', 'credits', 'credit amount', 'deposit', 'deposits', 'money in', 'paid in', 'amount in', 'in'],
        'debit' => ['debit', 'debits', 'debit amount', 'withdrawal', 'withdrawals', 'money out', 'paid out', 'amount out', 'out'],
        'currency' => ['currency', 'currency code', 'ccy'],
    ];

    /**
     * @param  list<list<string>>  $rows
     */
    private function __construct(
        private readonly string $fileName,
        private readonly array $rows,
    ) {}

    public static function fromString(string $fileName, string $contents): self
    {
        $contents = (string) preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw CouldNotReadStatement::because($fileName, __('it couldn’t be opened.'));
        }

        fwrite($handle, $contents);
        rewind($handle);

        $rows = [];

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $cells = array_map(fn (mixed $cell): string => mb_trim((string) $cell), $values);

            if (implode('', $cells) === '') {
                continue;
            }

            $rows[] = $cells;

            if (count($rows) > self::MAX_ROWS + 1) {
                fclose($handle);

                throw CouldNotReadStatement::because($fileName, __('it has more than :max rows. Export a shorter date range.', ['max' => number_format(self::MAX_ROWS)]));
            }
        }

        fclose($handle);

        return new self($fileName, $rows);
    }

    /**
     * The file's rows, blank ones left out.
     *
     * @return list<list<string>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * The file's transactions, read with the columns given, or else those
     * found from its headings, or else the account's saved columns when the
     * file is in the same layout.
     *
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public function read(CurrencyEnum $currency, ?CsvColumns $given = null, ?CsvColumns $saved = null): ReadStatement
    {
        if ($this->rows === []) {
            throw CouldNotReadStatement::because($this->fileName, __('it is empty.'));
        }

        $columns = $this->columns($given, $saved);
        $this->checkCurrency($columns, $currency);

        $lines = [];
        $unread = 0;
        $seen = [];

        foreach (array_slice($this->rows, $columns->hasHeader ? 1 : 0) as $row) {
            $line = $this->line($row, $columns, $seen);

            if ($line === null) {
                $unread++;

                continue;
            }

            $lines[] = $line;
        }

        if ($lines === []) {
            throw CouldNotReadStatement::because($this->fileName, __('no transactions were found in it. Check it is a statement exported as CSV.'));
        }

        return new ReadStatement($lines, $unread, $columns);
    }

    /**
     * What tells this file's layout apart: its headings, or its number of
     * columns when it has none.
     */
    public function layout(bool $hasHeader): string
    {
        return $hasHeader
            ? 'headings:'.implode('|', array_map(self::normalise(...), $this->rows[0] ?? []))
            : 'columns:'.count($this->rows[0] ?? []);
    }

    /**
     * @throws StatementNeedsColumns
     */
    private function columns(?CsvColumns $given, ?CsvColumns $saved): CsvColumns
    {
        $columnCount = count($this->rows[0]);

        if ($given !== null) {
            $given = $given->withLayout($this->layout($given->hasHeader));

            if ($given->isCompleteFor($columnCount)) {
                return $given;
            }
        }

        $found = $this->fromHeadings();

        if ($found !== null && $found->dateOrder !== null) {
            return $found;
        }

        if ($saved !== null && $saved->layout === $this->layout($saved->hasHeader) && $saved->isCompleteFor($columnCount)) {
            return $saved;
        }

        $guess = $found ?? $given ?? $this->guess();
        $dataRows = array_slice($this->rows, $guess->hasHeader ? 1 : 0, self::PREVIEW_ROWS);

        throw new StatementNeedsColumns(
            fileName: $this->fileName,
            headings: $guess->hasHeader
                ? $this->rows[0]
                : array_map(fn (int $index): string => __('Column :number', ['number' => $index + 1]), range(0, $columnCount - 1)),
            rows: $dataRows,
            guess: $guess->withLayout($this->layout($guess->hasHeader)),
        );
    }

    /**
     * Columns found from the first row's headings, with the date order when
     * the dates show it; null when the headings don't name a date,
     * description and amount.
     */
    private function fromHeadings(): ?CsvColumns
    {
        $single = [];
        $description = [];

        foreach ($this->rows[0] as $index => $heading) {
            $key = self::fieldFor($heading);

            if ($key === 'description') {
                $description[] = $index;
            } elseif ($key !== null && $key !== 'currency') {
                $single[$key] ??= $index;
            }
        }

        $columns = CsvColumns::fromStored([...$single, 'description' => $description, 'has_header' => true]);

        if ($columns === null) {
            return null;
        }

        return $columns
            ->withLayout($this->layout(true))
            ->withDateOrder(StatementDate::orderOf($this->column(array_slice($this->rows, 1), $columns->date)));
    }

    /**
     * A first guess for a file without headings: the first column of dates,
     * the first column of amounts after it, and the column with the most
     * words.
     */
    private function guess(): CsvColumns
    {
        $sample = array_slice($this->rows, 0, 20);
        $columnCount = count($this->rows[0]);
        $date = null;
        $amount = null;
        $description = null;
        $longest = -1;

        for ($index = 0; $index < $columnCount; $index++) {
            $values = array_values(array_filter($this->column($sample, $index), fn (string $value): bool => $value !== ''));

            if ($values === []) {
                continue;
            }

            if (array_all($values, StatementDate::looksLikeDate(...))) {
                $date ??= $index;

                continue;
            }

            if (array_all($values, fn (string $value): bool => StatementAmount::toCents($value) !== null)) {
                $amount ??= $index;

                continue;
            }

            $length = array_sum(array_map(mb_strlen(...), $values));

            if ($length > $longest) {
                $longest = $length;
                $description = $index;
            }
        }

        $date ??= 0;

        return new CsvColumns(
            hasHeader: false,
            date: $date,
            description: [$description ?? min(1, $columnCount - 1)],
            amount: $amount ?? min(2, $columnCount - 1),
            credit: null,
            debit: null,
            dateOrder: StatementDate::orderOf($this->column($sample, $date)) ?? StatementDate::DAY_FIRST,
        );
    }

    /**
     * @throws CouldNotReadStatement
     */
    private function checkCurrency(CsvColumns $columns, CurrencyEnum $currency): void
    {
        if (! $columns->hasHeader) {
            return;
        }

        foreach ($this->rows[0] as $index => $heading) {
            if (self::fieldFor($heading) !== 'currency') {
                continue;
            }

            foreach ($this->column(array_slice($this->rows, 1), $index) as $value) {
                if ($value !== '' && mb_strtoupper($value) !== $currency->value) {
                    throw CouldNotReadStatement::because($this->fileName, __('it is in :file but the account holds :account.', ['file' => mb_strtoupper($value), 'account' => $currency->value]));
                }
            }
        }
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $seen  how often each fingerprint has come up so far
     */
    private function line(array $row, CsvColumns $columns, array &$seen): ?StatementLine
    {
        $postedOn = StatementDate::parse($row[$columns->date] ?? '', $columns->dateOrder ?? StatementDate::DAY_FIRST);
        $amount = $this->amount($row, $columns);

        if ($postedOn === null || $amount === null) {
            return null;
        }

        $fingerprint = hash('sha256', implode("\x1f", array_map(self::normalise(...), $row)));
        $occurrence = $seen[$fingerprint] = ($seen[$fingerprint] ?? 0) + 1;

        return new StatementLine(
            postedOn: $postedOn,
            amountInCents: $amount,
            description: $this->description($row, $columns),
            externalId: 'csv:'.$fingerprint.':'.$occurrence,
        );
    }

    /**
     * @param  list<string>  $row
     */
    private function amount(array $row, CsvColumns $columns): ?int
    {
        if ($columns->amount !== null) {
            return StatementAmount::toCents($row[$columns->amount] ?? '');
        }

        $credit = $columns->credit === null ? null : StatementAmount::toCents($row[$columns->credit] ?? '');
        $debit = $columns->debit === null ? null : StatementAmount::toCents($row[$columns->debit] ?? '');

        if ($credit !== null && $credit !== 0) {
            return abs($credit);
        }

        if ($debit !== null) {
            return -abs($debit);
        }

        return $credit;
    }

    /**
     * @param  list<string>  $row
     */
    private function description(array $row, CsvColumns $columns): string
    {
        $parts = [];

        foreach ($columns->description as $index) {
            $part = mb_trim((string) preg_replace('/\s+/', ' ', $row[$index] ?? ''));

            if ($part !== '' && ! in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return mb_substr(implode(' ', $parts), 0, 1000) ?: __('(no description)');
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<string>
     */
    private function column(array $rows, int $index): array
    {
        return array_map(fn (array $row): string => $row[$index] ?? '', $rows);
    }

    private static function fieldFor(string $heading): ?string
    {
        $normalised = self::normalise($heading);

        foreach (self::HEADINGS as $field => $headings) {
            if (in_array($normalised, $headings, true)) {
                return $field;
            }
        }

        return null;
    }

    private static function normalise(string $value): string
    {
        return mb_trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($value)));
    }
}
