<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use Mom\Data\AbstractString;

final class Summary extends AbstractString
{
    public static function getName(): string
    {
        return 'summary';
    }
}
