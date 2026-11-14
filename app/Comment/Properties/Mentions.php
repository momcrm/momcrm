<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use Mom\Data\AbstractCollection;

final class Mentions extends AbstractCollection
{
    public static function getName(): string
    {
        return 'mentions';
    }
}
