<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use Mom\Data\AbstractInteger;

final class VersionMinor extends AbstractInteger
{
    public static function getName(): string
    {
        return 'version_minor';
    }
}
