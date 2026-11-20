<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

/**
 * Reads an amount the way banks write them: "1,234.50", "$1234.50",
 * "-12.00", "(12.00)", "12.00 DR" or "12.00 CR".
 */
final class StatementAmount
{
    /**
     * The amount in cents, negative for money out, or null when the text
     * isn't an amount.
     */
    public static function toCents(string $value): ?int
    {
        $text = mb_strtoupper(mb_trim($value));

        if ($text === '') {
            return null;
        }

        $isNegative = str_starts_with($text, '-')
            || (str_starts_with($text, '(') && str_ends_with($text, ')'))
            || str_ends_with($text, 'DR');

        $digits = (string) preg_replace('/[^0-9.]/', '', (string) preg_replace('/(CR|DR)$/', '', $text));

        if (preg_match('/^\d+(\.\d+)?$/', $digits) !== 1) {
            return null;
        }

        $cents = (int) round(((float) $digits) * 100);

        return $isNegative ? -$cents : $cents;
    }
}
