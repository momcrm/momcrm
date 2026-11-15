<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, AccessRule>
 */
final class AccessRuleCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return AccessRule::class;
    }
}
