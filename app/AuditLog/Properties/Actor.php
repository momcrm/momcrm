<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who acted, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class Actor extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'actor';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
