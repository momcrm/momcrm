<?php

declare(strict_types=1);

namespace App\Group;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Group>
 */
final class GroupCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Group::class;
    }
}
