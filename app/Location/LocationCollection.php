<?php

declare(strict_types=1);

namespace App\Location;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Location>
 */
final class LocationCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Location::class;
    }
}
