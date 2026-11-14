<?php

declare(strict_types=1);

namespace App\Activity\Properties;

use Mom\Data\AbstractCollection;

final class Details extends AbstractCollection
{
    public static function getName(): string
    {
        return 'details';
    }
}
