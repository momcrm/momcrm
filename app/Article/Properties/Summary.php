<?php

declare(strict_types=1);

namespace App\Article\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * A sentence or two saying what the article is for, shown on the list.
 */
final class Summary extends AbstractString
{
    public static function getName(): string
    {
        return 'summary';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:500'];
    }
}
