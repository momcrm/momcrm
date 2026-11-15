<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Project\Project as ProjectData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The project the ticket is in, when loaded.
 *
 * @extends AbstractRelatedData<ProjectData>
 */
final class Project extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'project';
    }

    public static function dataClass(): string
    {
        return ProjectData::class;
    }
}
