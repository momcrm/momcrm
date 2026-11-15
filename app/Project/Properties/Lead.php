<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who leads the project, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class Lead extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'lead';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
