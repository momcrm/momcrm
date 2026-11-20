<?php

declare(strict_types=1);

namespace App\Article\Properties;

use Mom\Data\AbstractString;

/**
 * The body as plain text, kept beside the HTML so it can be searched.
 */
final class BodyText extends AbstractString
{
    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'body_text';
    }
}
