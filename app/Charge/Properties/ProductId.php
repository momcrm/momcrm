<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ProductId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'product_id';
    }
}
