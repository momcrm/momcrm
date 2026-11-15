<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who the ticket is assigned to, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class Assignee extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'assignee';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
