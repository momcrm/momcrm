<?php

declare(strict_types=1);

use App\BankTransaction\Statements\CouldNotReadStatement;
use App\BankTransaction\Statements\OfxStatement;
use App\BankTransaction\Statements\StatementLine;
use App\Shared\CurrencyEnum;

const SGML_OFX = <<<'OFX'
OFXHEADER:100
DATA:OFXSGML
VERSION:102

<OFX>
<BANKMSGSRSV1><STMTTRNRS><STMTRS>
<CURDEF>AUD
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20251113000000[+10:AEST]
<TRNAMT>250.00
<FITID>2025111301
<NAME>HARBOUR DENTAL
<MEMO>INV-0042 &amp; INV-0043
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20251114
<TRNAMT>-19.95
<FITID>2025111402
<NAME>TELSTRA
</STMTTRN>
</BANKTRANLIST>
</STMTRS></STMTTRNRS></BANKMSGSRSV1>
</OFX>
OFX;

const XML_OFX = <<<'OFX'
<?xml version="1.0" encoding="UTF-8"?>
<?OFX OFXHEADER="200" VERSION="220"?>
<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS>
<CURDEF>AUD</CURDEF>
<BANKTRANLIST>
<STMTTRN><TRNTYPE>CREDIT</TRNTYPE><DTPOSTED>20251113</DTPOSTED><TRNAMT>1250.50</TRNAMT><FITID>A1</FITID><NAME>Coastal Logistics</NAME></STMTTRN>
</BANKTRANLIST>
</STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>
OFX;

/**
 * @return list<array{0: string, 1: int, 2: string, 3: string}>
 */
function ofx_lines(string $ofx, CurrencyEnum $currency = CurrencyEnum::AUD): array
{
    return array_map(
        fn (StatementLine $line): array => [$line->postedOn->toDateString(), $line->amountInCents, $line->description, $line->externalId],
        OfxStatement::fromString('statement.ofx', $ofx)->read($currency)->lines,
    );
}

it('reads the older SGML form, whose values have no closing tags', function (): void {
    expect(ofx_lines(SGML_OFX))->toBe([
        ['2025-11-13', 25000, 'HARBOUR DENTAL INV-0042 & INV-0043', 'ofx:2025111301'],
        ['2025-11-14', -1995, 'TELSTRA', 'ofx:2025111402'],
    ]);
});

it('reads the XML form', function (): void {
    expect(ofx_lines(XML_OFX))->toBe([['2025-11-13', 125050, 'Coastal Logistics', 'ofx:A1']]);
});

it('recognises OFX by its contents, not its name', function (): void {
    expect(OfxStatement::recognises(SGML_OFX))->toBeTrue()
        ->and(OfxStatement::recognises(XML_OFX))->toBeTrue()
        ->and(OfxStatement::recognises("Date,Description,Amount\n"))->toBeFalse();
});

it('refuses a statement in another currency', function (): void {
    expect(fn () => ofx_lines(SGML_OFX, CurrencyEnum::NZD))
        ->toThrow(CouldNotReadStatement::class, 'statement.ofx: it is in AUD but the account holds NZD.');
});
