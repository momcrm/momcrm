<?php

declare(strict_types=1);

namespace App\Project;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Project>
 */
final class ProjectCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Project::class;
    }
}
