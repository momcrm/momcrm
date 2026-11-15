<?php

declare(strict_types=1);

namespace App\Shared;

use Stringable;
use UnexpectedValueException;

/**
 * Narrows values that arrive untyped (database aggregates, plucked columns,
 * validated input, JSON) to the type the code expects, and fails loudly when
 * the value is something else rather than quietly casting it.
 */
final class Types
{
    public static function string(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value), $value instanceof Stringable => (string) $value,
            default => throw new UnexpectedValueException('Expected a string, got '.get_debug_type($value).'.'),
        };
    }

    public static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    /**
     * Whole numbers, including the numeric strings some database drivers
     * return for counts and sums.
     */
    public static function int(mixed $value): int
    {
        return match (true) {
            is_int($value) => $value,
            is_float($value) && floor($value) === $value => (int) $value,
            is_string($value) && preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            default => throw new UnexpectedValueException('Expected a whole number, got '.get_debug_type($value).'.'),
        };
    }

    /**
     * A count or sum that is null when there was nothing to count.
     */
    public static function intOrZero(mixed $value): int
    {
        return $value === null ? 0 : self::int($value);
    }

    /**
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        if (! is_iterable($value)) {
            throw new UnexpectedValueException('Expected a list, got '.get_debug_type($value).'.');
        }

        $strings = [];

        foreach ($value as $item) {
            $strings[] = self::string($item);
        }

        return $strings;
    }

    /**
     * An array keyed by name, such as validated input.
     *
     * @return array<string, mixed>
     */
    public static function stringKeyed(mixed $value): array
    {
        if (! is_array($value)) {
            throw new UnexpectedValueException('Expected an array, got '.get_debug_type($value).'.');
        }

        $keyed = [];

        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw new UnexpectedValueException('Expected string keys, got '.get_debug_type($key).'.');
            }

            $keyed[$key] = $item;
        }

        return $keyed;
    }
}
