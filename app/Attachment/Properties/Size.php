<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use Mom\Data\AbstractInteger;

final class Size extends AbstractInteger
{
    public static function getName(): string
    {
        return 'size';
    }
}
