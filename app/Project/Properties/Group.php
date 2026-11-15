<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Group\Group as GroupData;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The group the project belongs to, when loaded.
 *
 * @extends AbstractRelatedData<GroupData>
 */
final class Group extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'group';
    }

    public static function dataClass(): string
    {
        return GroupData::class;
    }
}
