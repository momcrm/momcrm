<?php

declare(strict_types=1);

namespace App\Service\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * A short name for the service, unique to its customer, such as a domain or
 * an email address. Made from the name when left blank.
 */
final class ShortName extends AbstractString
{
    public const string PATTERN = '/^[A-Za-z0-9@._-]+$/';

    public static function getName(): string
    {
        return 'short_name';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255', 'regex:'.self::PATTERN];
    }
}
