<?php

declare(strict_types=1);

use App\BankTransaction\Statements\BpayStatement;
use App\BankTransaction\Statements\CouldNotReadStatement;
use App\BankTransaction\Statements\StatementLine;
use App\Shared\CurrencyEnum;

const BPAY_HEADINGS = 'Data Type,Payment Instruction Type,Transaction Type,Payer institution code,Payment Account Detail,Country of Payment,State of Payment,Currency of Payment,Biller/Agency Code,Service Code,Customer Reference Number,Payment Method,Entry Method,Amount,Transaction Reference Number,Original Reference Number,Settlement Date,Date Payment Accepted,Time Payment Accepted,Payer Name';

function bpay_row(string $type, string $crn, string $amount, string $reference, string $payer = 'GLENDA CITIZEN', string $currency = 'AUD'): string
{
    return "BPAY,05,{$type},CBA,,AUS,WA,{$currency},0000099999,0000000,{$crn},1,004,{$amount},{$reference},,20251114,20251113,101500,{$payer}";
}

/**
 * @return list<array{0: string, 1: int, 2: string, 3: string, 4: ?string, 5: ?string}>
 */
function bpay_lines(string $csv, CurrencyEnum $currency = CurrencyEnum::AUD): array
{
    return array_map(
        fn (StatementLine $line): array => [$line->postedOn->toDateString(), $line->amountInCents, $line->description, $line->externalId, $line->crn, $line->payerName],
        BpayStatement::fromString('bpay.csv', $csv)->read($currency)->lines,
    );
}

it('reads the payments in a BPAY biller file', function (): void {
    $csv = implode("\n", [BPAY_HEADINGS, bpay_row('0', '0000422', '1250.00', 'CBA2025111300001'), bpay_row('0', '18', '40.00', 'CBA2025111300002', payer: ''), bpay_row('1', '422', '1250.00', 'CBA2025111300003')]);

    expect(bpay_lines($csv))->toBe([
        ['2025-11-13', 125000, 'GLENDA CITIZEN', 'bpay:CBA2025111300001', '422', 'GLENDA CITIZEN'],
        ['2025-11-13', 4000, 'BPAY CRN 18', 'bpay:CBA2025111300002', '18', null],
        ['2025-11-13', -125000, 'GLENDA CITIZEN', 'bpay:CBA2025111300003', '422', 'GLENDA CITIZEN'],
    ]);
});

it('refuses a file that is not a BPAY biller file, or in another currency', function (string $csv, string $message): void {
    expect(fn () => bpay_lines($csv))->toThrow(CouldNotReadStatement::class, $message);
})->with([
    'a bank statement' => ["Date,Description,Amount\n13/11/2025,Deposit,10.00\n", 'bpay.csv: it isn’t a BPAY biller file.'],
    'another currency' => [BPAY_HEADINGS."\n".bpay_row('0', '422', '10.00', 'R1', currency: 'NZD'), 'bpay.csv: it is in NZD but the account holds AUD.'],
]);
