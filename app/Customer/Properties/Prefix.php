<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * A short code the team files the customer under, such as ACME. Letters
 * only, stored in capitals, unique within the team.
 */
final class Prefix extends AbstractString
{
    public static function getName(): string
    {
        return 'prefix';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:8', 'regex:/^[A-Za-z]+$/'];
    }

    /**
     * The prefix as the request sent it, in capitals, or null when blank.
     */
    public static function normalise(mixed $prefix): ?string
    {
        if (! is_string($prefix) || trim($prefix) === '') {
            return null;
        }

        return mb_strtoupper(trim($prefix));
    }
}
