<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who saved the article's current version, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class EditedBy extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'editedBy';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
