<?php

declare(strict_types=1);

namespace App\Milestone\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ProjectId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'project_id';
    }
}
