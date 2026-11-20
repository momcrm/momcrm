<?php

declare(strict_types=1);

namespace App\Customer;

/**
 * A customer's BPAY customer reference number (CRN): their customer number
 * followed by a check digit worked out by MOD10V01 (Luhn), so a mistyped
 * reference is caught by the payer's bank before any money moves.
 */
final class BpayReference
{
    public static function forCustomerNumber(int $number): string
    {
        return $number.self::checkDigit((string) $number);
    }

    /**
     * The customer number a reference was made from, or null when it isn't
     * a reference with a right check digit.
     */
    public static function customerNumberOf(string $reference): ?int
    {
        $digits = mb_ltrim(mb_trim($reference), '0');

        if (preg_match('/^\d{2,20}$/', $digits) !== 1) {
            return null;
        }

        $number = mb_substr($digits, 0, -1);

        return self::checkDigit($number) === (int) mb_substr($digits, -1) ? (int) $number : null;
    }

    private static function checkDigit(string $number): int
    {
        $sum = 0;

        foreach (array_reverse(mb_str_split($number)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 0 ? 2 : 1);
            $sum += intdiv($value, 10) + $value % 10;
        }

        return (10 - $sum % 10) % 10;
    }
}
