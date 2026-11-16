<?php

declare(strict_types=1);

namespace App\Secret;

use Illuminate\Http\Request;

/**
 * A secret's key/value pairs as a form sends them: at least one pair, each
 * with a key and a value, such as username and password.
 */
final class SecretValues
{
    public const string FIELD = 'values';

    public const int MAX_PAIRS = 50;

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $isRequired = true): array
    {
        return [
            self::FIELD => [$isRequired ? 'required' : 'sometimes', 'array', 'min:1', 'max:'.self::MAX_PAIRS],
            self::FIELD.'.*.key' => ['required', 'string', 'max:255', 'distinct'],
            self::FIELD.'.*.value' => ['required', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::FIELD.'.required' => __('Add at least one key and value, such as a username and password.'),
            self::FIELD.'.min' => __('Add at least one key and value, such as a username and password.'),
            self::FIELD.'.*.key.required' => __('Give every value a key, such as "password".'),
            self::FIELD.'.*.key.distinct' => __('Each key can be used once.'),
            self::FIELD.'.*.value.required' => __('Fill in every value, or remove its row.'),
        ];
    }

    /**
     * The validated pairs, keeping only key and value.
     *
     * @param  array<string, mixed>  $validated
     * @return list<array{key: string, value: string}>|null null when none were sent
     */
    public static function from(array $validated): ?array
    {
        $pairs = $validated[self::FIELD] ?? null;

        if (! is_array($pairs)) {
            return null;
        }

        return array_values(array_map(
            fn (mixed $pair): array => [
                'key' => is_array($pair) && is_string($pair['key'] ?? null) ? $pair['key'] : '',
                'value' => is_array($pair) && is_string($pair['value'] ?? null) ? $pair['value'] : '',
            ],
            $pairs,
        ));
    }

    /**
     * Trims keys (never values, where spaces may matter).
     */
    public static function prepare(Request $request): void
    {
        $pairs = $request->input(self::FIELD);

        if (! is_array($pairs)) {
            return;
        }

        $request->merge([self::FIELD => array_map(
            fn (mixed $pair): mixed => is_array($pair) && is_string($pair['key'] ?? null) ? [...$pair, 'key' => trim($pair['key'])] : $pair,
            $pairs,
        )]);
    }
}
