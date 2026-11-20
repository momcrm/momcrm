<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use App\Shared\CurrencyEnum;

/**
 * A BPAY biller file: the payments customers made to the team's biller
 * code, as the bank's CSV report with a heading row. Each payment carries
 * the customer reference number (CRN) it was made with and the bank's
 * transaction reference, which tells it apart on a re-import. Rows that
 * aren't payments (reversals, corrections) are counted as money out.
 */
final class BpayStatement
{
    /**
     * Accepted headings for each column, compared after lowercasing and
     * replacing anything that is not a letter or digit with a space.
     *
     * @var array<string, list<string>>
     */
    private const array HEADINGS = [
        'crn' => ['customer reference number', 'crn', 'customer reference'],
        'amount' => ['amount', 'payment amount'],
        'reference' => ['transaction reference number', 'trn', 'transaction reference', 'receipt number'],
        'date' => ['date payment accepted', 'payment date', 'settlement date', 'date'],
        'payer' => ['payer name', 'payer'],
        'type' => ['transaction type'],
        'currency' => ['currency of payment', 'currency'],
    ];

    private function __construct(
        private readonly string $fileName,
        private readonly CsvStatement $csv,
    ) {}

    public static function fromString(string $fileName, string $contents): self
    {
        return new self($fileName, CsvStatement::fromString($fileName, $contents));
    }

    /**
     * @throws CouldNotReadStatement
     */
    public function read(CurrencyEnum $currency): ReadStatement
    {
        $rows = $this->csv->rows();
        $columns = $this->columns($rows[0] ?? []);

        if (! isset($columns['crn'], $columns['amount'], $columns['reference'], $columns['date'])) {
            throw CouldNotReadStatement::because($this->fileName, __('it isn’t a BPAY biller file. It needs Customer Reference Number, Amount, Transaction Reference Number and a date column.'));
        }

        $lines = [];
        $unread = 0;

        foreach (array_slice($rows, 1) as $row) {
            $value = fn (string $column): string => isset($columns[$column]) ? mb_trim($row[$columns[$column]] ?? '') : '';
            $rowCurrency = mb_strtoupper($value('currency'));

            if ($rowCurrency !== '' && $rowCurrency !== $currency->value) {
                throw CouldNotReadStatement::because($this->fileName, __('it is in :file but the account holds :account.', ['file' => $rowCurrency, 'account' => $currency->value]));
            }

            $postedOn = StatementDate::parse($value('date'), StatementDate::DAY_FIRST);
            $amount = StatementAmount::toCents($value('amount'));
            $reference = $value('reference');

            if ($postedOn === null || $amount === null || $reference === '') {
                $unread++;

                continue;
            }

            $crn = mb_ltrim($value('crn'), '0');
            $payer = $value('payer');
            $isPayment = ! isset($columns['type']) || (int) $value('type') === 0;

            $lines[] = new StatementLine(
                postedOn: $postedOn,
                amountInCents: $isPayment ? abs($amount) : -abs($amount),
                description: $payer !== '' ? $payer : __('BPAY CRN :crn', ['crn' => $crn]),
                externalId: 'bpay:'.$reference,
                crn: $crn === '' ? null : $crn,
                payerName: $payer === '' ? null : $payer,
            );
        }

        if ($lines === []) {
            throw CouldNotReadStatement::because($this->fileName, __('no payments were found in it.'));
        }

        return new ReadStatement($lines, $unread);
    }

    /**
     * Where each column is, by the first of its accepted headings the file
     * has: the date a payment was accepted beats its settlement date.
     *
     * @param  list<string>  $headings
     * @return array<string, int>
     */
    private function columns(array $headings): array
    {
        $normalised = array_map(fn (string $heading): string => mb_trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($heading))), $headings);
        $columns = [];

        foreach (self::HEADINGS as $column => $accepted) {
            foreach ($accepted as $heading) {
                $index = array_search($heading, $normalised, true);

                if ($index !== false) {
                    $columns[$column] = $index;

                    break;
                }
            }
        }

        return $columns;
    }
}
