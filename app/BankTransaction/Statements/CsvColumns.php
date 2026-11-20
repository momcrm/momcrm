<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

/**
 * Which columns of a CSV statement hold what, counted from 0, and how its
 * dates are written. The money is in one amount column (negative for money
 * out), or in a credit column with a debit column beside it.
 *
 * The layout says which files the columns fit: their headings, or how many
 * columns a file without headings has.
 */
final readonly class CsvColumns
{
    /**
     * @param  list<int>  $description
     */
    public function __construct(
        public bool $hasHeader,
        public int $date,
        public array $description,
        public ?int $amount,
        public ?int $credit,
        public ?int $debit,
        public ?string $dateOrder,
        public string $layout = '',
    ) {}

    /**
     * Columns as stored on an account or sent from the columns form, or null
     * when they don't say where the date, description and money are.
     *
     * @param  array<mixed>  $stored
     */
    public static function fromStored(array $stored): ?self
    {
        $index = fn (string $key): ?int => isset($stored[$key]) && is_numeric($stored[$key]) && (int) $stored[$key] >= 0 ? (int) $stored[$key] : null;
        $description = array_values(array_unique(array_map(
            intval(...),
            array_filter(is_array($stored['description'] ?? null) ? $stored['description'] : [], fn (mixed $value): bool => is_numeric($value) && (int) $value >= 0),
        )));
        $date = $index('date');
        $dateOrder = is_string($stored['date_order'] ?? null) && in_array($stored['date_order'], StatementDate::ORDERS, true) ? $stored['date_order'] : null;

        if ($date === null || $description === [] || ($index('amount') === null && $index('credit') === null)) {
            return null;
        }

        return new self(
            hasHeader: filter_var($stored['has_header'] ?? false, FILTER_VALIDATE_BOOLEAN),
            date: $date,
            description: $description,
            amount: $index('amount'),
            credit: $index('amount') === null ? $index('credit') : null,
            debit: $index('amount') === null ? $index('debit') : null,
            dateOrder: $dateOrder,
            layout: is_string($stored['layout'] ?? null) ? $stored['layout'] : '',
        );
    }

    /**
     * @return array{has_header: bool, date: int, description: list<int>, amount: ?int, credit: ?int, debit: ?int, date_order: ?string, layout: string}
     */
    public function toStored(): array
    {
        return [
            'has_header' => $this->hasHeader,
            'date' => $this->date,
            'description' => $this->description,
            'amount' => $this->amount,
            'credit' => $this->credit,
            'debit' => $this->debit,
            'date_order' => $this->dateOrder,
            'layout' => $this->layout,
        ];
    }

    public function withLayout(string $layout): self
    {
        return new self($this->hasHeader, $this->date, $this->description, $this->amount, $this->credit, $this->debit, $this->dateOrder, $layout);
    }

    public function withDateOrder(?string $dateOrder): self
    {
        return new self($this->hasHeader, $this->date, $this->description, $this->amount, $this->credit, $this->debit, $dateOrder, $this->layout);
    }

    /**
     * Whether every column named is one the file has, and the date order is known.
     */
    public function isCompleteFor(int $columnCount): bool
    {
        $named = [$this->date, ...$this->description, $this->amount, $this->credit, $this->debit];

        foreach ($named as $column) {
            if ($column !== null && $column >= $columnCount) {
                return false;
            }
        }

        return $this->dateOrder !== null;
    }
}
