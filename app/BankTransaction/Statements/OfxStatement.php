<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use App\Shared\CurrencyEnum;
use Carbon\CarbonImmutable;

/**
 * A bank statement exported as OFX or QFX (Quicken), in either the older
 * SGML form, whose values have no closing tags, or the XML form. Each
 * transaction carries the bank's own id (FITID), which is what tells it
 * apart when the same statement is imported again.
 */
final class OfxStatement
{
    private function __construct(
        private readonly string $fileName,
        private readonly string $contents,
    ) {}

    /**
     * Whether the file is OFX, whatever it is named.
     */
    public static function recognises(string $contents): bool
    {
        return preg_match('/OFXHEADER:|<OFX>/i', mb_substr($contents, 0, 2000)) === 1;
    }

    public static function fromString(string $fileName, string $contents): self
    {
        return new self($fileName, $contents);
    }

    /**
     * @throws CouldNotReadStatement
     */
    public function read(CurrencyEnum $currency): ReadStatement
    {
        $statementCurrency = $this->value($this->contents, 'CURDEF');

        if ($statementCurrency !== null && mb_strtoupper($statementCurrency) !== $currency->value) {
            throw CouldNotReadStatement::because($this->fileName, __('it is in :file but the account holds :account.', ['file' => mb_strtoupper($statementCurrency), 'account' => $currency->value]));
        }

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $this->contents, $matches);

        $lines = [];
        $unread = 0;

        foreach ($matches[1] as $block) {
            $line = $this->line($block);

            if ($line === null) {
                $unread++;

                continue;
            }

            $lines[] = $line;
        }

        if ($lines === []) {
            throw CouldNotReadStatement::because($this->fileName, __('no transactions were found in it.'));
        }

        return new ReadStatement($lines, $unread);
    }

    private function line(string $block): ?StatementLine
    {
        $postedOn = $this->date($this->value($block, 'DTPOSTED'));
        $amount = StatementAmount::toCents($this->value($block, 'TRNAMT') ?? '');

        if ($postedOn === null || $amount === null) {
            return null;
        }

        $name = $this->value($block, 'NAME') ?? $this->value($block, 'PAYEEID');
        $memo = $this->value($block, 'MEMO');
        $description = implode(' ', array_values(array_unique(array_filter([$name, $memo], fn (?string $part): bool => $part !== null && $part !== ''))));
        $fitId = $this->value($block, 'FITID');

        return new StatementLine(
            postedOn: $postedOn,
            amountInCents: $amount,
            description: mb_substr($description, 0, 1000) ?: __('(no description)'),
            externalId: $fitId !== null && $fitId !== ''
                ? 'ofx:'.$fitId
                : 'ofx:'.hash('sha256', $postedOn->toDateString().'|'.$amount.'|'.$description),
        );
    }

    /**
     * A value's text: up to its closing tag in XML, or to the next tag or
     * line in SGML.
     */
    private function value(string $text, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'>([^<\r\n]*)/i', $text, $matches) !== 1) {
            return null;
        }

        $value = mb_trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_XML1));

        return $value === '' ? null : (string) preg_replace('/\s+/', ' ', $value);
    }

    /**
     * OFX dates start YYYYMMDD, and may go on with a time and a zone.
     */
    private function date(?string $value): ?CarbonImmutable
    {
        if ($value === null || preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
            ? CarbonImmutable::create((int) $matches[1], (int) $matches[2], (int) $matches[3])?->startOfDay()
            : null;
    }
}
