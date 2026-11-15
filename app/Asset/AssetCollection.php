<?php

declare(strict_types=1);

namespace App\Asset;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Asset>
 */
final class AssetCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Asset::class;
    }
}
