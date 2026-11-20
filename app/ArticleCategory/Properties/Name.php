<?php

declare(strict_types=1);

namespace App\ArticleCategory\Properties;

use Mom\Data\AbstractString;

final class Name extends AbstractString
{
    public static function getName(): string
    {
        return 'name';
    }
}
