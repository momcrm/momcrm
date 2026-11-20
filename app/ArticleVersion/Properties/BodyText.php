<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use Mom\Data\AbstractString;

final class BodyText extends AbstractString
{
    public static bool $includeInResponse = false;

    public static function getName(): string
    {
        return 'body_text';
    }
}
