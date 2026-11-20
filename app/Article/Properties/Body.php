<?php

declare(strict_types=1);

namespace App\Article\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The article itself, as HTML from the editor. It is only ever stored after
 * ArticleBody::sanitise(), so it can be shown as it is.
 */
final class Body extends AbstractString
{
    public const int MAX_LENGTH = 1_000_000;

    public static function getName(): string
    {
        return 'body';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:'.self::MAX_LENGTH];
    }
}
