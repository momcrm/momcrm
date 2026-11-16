<?php

declare(strict_types=1);

namespace App\Secret;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Secret>
 */
final class SecretCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Secret::class;
    }
}
