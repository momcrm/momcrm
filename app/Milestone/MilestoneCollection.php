<?php

declare(strict_types=1);

namespace App\Milestone;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Milestone>
 */
final class MilestoneCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Milestone::class;
    }
}
