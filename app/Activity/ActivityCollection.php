<?php

declare(strict_types=1);

namespace App\Activity;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Activity>
 */
final class ActivityCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Activity::class;
    }
}
