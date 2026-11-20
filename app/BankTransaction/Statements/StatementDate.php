<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Reads the dates banks put in statements: 03/11/2025, 3-11-25, 2025-11-03,
 * 20251103 or 3 Nov 2025, with or without a time after them. A date written
 * only in numbers needs to know whether the day or the month comes first.
 */
final class StatementDate
{
    public const string DAY_FIRST = 'dmy';

    public const string MONTH_FIRST = 'mdy';

    public const string YEAR_FIRST = 'ymd';

    /**
     * @var list<string>
     */
    public const array ORDERS = [self::DAY_FIRST, self::MONTH_FIRST, self::YEAR_FIRST];

    /**
     * @var list<string>
     */
    private const array WORD_FORMATS = [
        'j M Y', 'd M Y', 'j M y', 'd M y', 'j-M-Y', 'd-M-Y', 'j-M-y', 'd-M-y', 'j/M/Y', 'd/M/Y', 'j/M/y', 'd/M/y',
        'j F Y', 'd F Y', 'M j Y', 'M j, Y', 'F j, Y', 'D j M Y', 'D, j M Y',
    ];

    public static function parse(string $value, string $order): ?CarbonImmutable
    {
        $text = self::withoutTime($value);

        if ($text === '') {
            return null;
        }

        $parts = self::numericParts($text);

        if ($parts !== null) {
            return self::fromParts($parts, strlen($parts[0]) === 4 ? self::YEAR_FIRST : $order);
        }

        if (preg_match('/[a-z]/i', $text) !== 1) {
            return null;
        }

        foreach (self::WORD_FORMATS as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $text);
            } catch (Throwable) {
                continue;
            }

            // Written back the same way, so 31 Feb isn't read as 3 March.
            if ($date instanceof CarbonImmutable && mb_strtolower($date->format($format)) === mb_strtolower($text)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Whether the text reads as a date one way or another.
     */
    public static function looksLikeDate(string $value): bool
    {
        foreach (self::ORDERS as $order) {
            if (self::parse($value, $order) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Which order a column of dates is written in, or null when it can't be
     * told: every day is the 12th or before, so either order reads.
     *
     * @param  list<string>  $values
     */
    public static function orderOf(array $values): ?string
    {
        $dayFirst = true;
        $monthFirst = true;
        $isNumeric = false;

        foreach ($values as $value) {
            $parts = self::numericParts(self::withoutTime($value));

            if ($parts === null) {
                continue;
            }

            $isNumeric = true;

            if (strlen($parts[0]) === 4) {
                return self::YEAR_FIRST;
            }

            $dayFirst = $dayFirst && self::fromParts($parts, self::DAY_FIRST) !== null;
            $monthFirst = $monthFirst && self::fromParts($parts, self::MONTH_FIRST) !== null;
        }

        if (! $isNumeric) {
            return self::DAY_FIRST;
        }

        return match (true) {
            $dayFirst && ! $monthFirst => self::DAY_FIRST,
            $monthFirst && ! $dayFirst => self::MONTH_FIRST,
            default => null,
        };
    }

    private static function withoutTime(string $value): string
    {
        return mb_trim((string) preg_replace('/[ T]\d{1,2}:\d{2}(:\d{2})?(\.\d+)?\s*([AP]M|Z|[+-]\d{2}:?\d{2})?$/i', '', mb_trim($value)));
    }

    /**
     * The three numbers of a date written only in numbers, or null.
     *
     * @return array{0: string, 1: string, 2: string}|null
     */
    private static function numericParts(string $text): ?array
    {
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $text, $matches) === 1) {
            return [$matches[1], $matches[2], $matches[3]];
        }

        if (preg_match('/^(\d{1,4})[\/.\-](\d{1,2})[\/.\-](\d{1,4})$/', $text, $matches) === 1) {
            return [$matches[1], $matches[2], $matches[3]];
        }

        return null;
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $parts
     */
    private static function fromParts(array $parts, string $order): ?CarbonImmutable
    {
        [$year, $month, $day] = match ($order) {
            self::YEAR_FIRST => [$parts[0], $parts[1], $parts[2]],
            self::MONTH_FIRST => [$parts[2], $parts[0], $parts[1]],
            default => [$parts[2], $parts[1], $parts[0]],
        };

        if ($order !== self::YEAR_FIRST && strlen($parts[0]) === 4) {
            return null;
        }

        $year = (int) (strlen($year) <= 2 ? '20'.mb_str_pad($year, 2, '0', STR_PAD_LEFT) : $year);

        if (! checkdate((int) $month, (int) $day, $year) || $year < 1970 || $year > 2100) {
            return null;
        }

        return CarbonImmutable::create($year, (int) $month, (int) $day)?->startOfDay();
    }
}
